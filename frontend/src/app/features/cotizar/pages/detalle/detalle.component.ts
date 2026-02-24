import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';
import { AuthService } from '../../../../core/services/auth.service';

type BillingType = 'per_day' | 'per_reservation';

interface SeguroOption {
  id: string;
  label: string;
  description: string;
  pctPerDay: number; // % del dailyRate por día
}

interface AdicionalOption {
  id: string;
  label: string;
  description: string;
  billing: BillingType;
  quantity: number;
  maxQuantity: number;
}

type RatingItem = { rating: number | null; comment: string | null; date: string | null };

@Component({
  selector: 'app-detalle',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './detalle.component.html',
  styleUrls: ['./detalle.component.css'],
})
export class DetalleComponent implements OnInit {
  vehiculo?: VehicleOption;

  dias = 1;
  total = 0;

  startAt = '';
  endAt = '';
  pickupLocationId = 1;
  dropoffLocationId = 1;

  unitsAvailable?: number;
  checking = false;
  creating = false;
  errorMsg = '';

  // ratings
  ratingLoading = false;
  ratingError = '';
  ratingAvg: number | null = null;
  ratingCount = 0;
  ratingItems: RatingItem[] = [];

  // ✅ seguro = % por día del dailyRate
  seguros: SeguroOption[] = [
    {
      id: 'smart',
      label: 'SMART COVER',
      description: 'Cobertura que reduce 100% la franquicia por daños de colisión y vuelco.',
      pctPerDay: 0.05, // 5%
    },
    {
      id: 'plus',
      label: 'PLUS COVER',
      description: 'Cobertura que reduce 100% la franquicia por daños de colisión.',
      pctPerDay: 0.08, // 8%
    },
    {
      id: 'tyres',
      label: 'CUBIERTAS COVER',
      description: 'Cobertura adicional que reduce 100% la franquicia por daños y roturas de cubiertas.',
      pctPerDay: 0.03, // 3%
    },
  ];

  selectedSeguroId: string | null = null;

  // ✅ extras = 3% del base por unidad (si billing per_day => x días)
  // OJO: debe ser público/readonly para poder usarse en el HTML
  readonly EXTRAS_PCT_OF_BASE = 0.03;

  adicionales: AdicionalOption[] = [
    { id: 'young_driver', label: 'Conductor joven', description: 'Si tenés entre 18 y 20 años podés alquilar un auto.', billing: 'per_day', quantity: 0, maxQuantity: 1 },
    { id: 'additional_driver', label: 'Conductor adicional', description: 'Persona autorizada para conducir el vehículo aparte del conductor principal.', billing: 'per_day', quantity: 0, maxQuantity: 2 },
    { id: 'baby_seat', label: 'Silla de bebé (1–3 años)', description: 'Silla especial para bebés entre 1 y 3 años.', billing: 'per_day', quantity: 0, maxQuantity: 2 },
    { id: 'booster', label: 'Silla de niño (4–10 años)', description: 'Silla especial para niños de 4 a 10 años.', billing: 'per_day', quantity: 0, maxQuantity: 2 },
    { id: 'border_cross', label: 'Cruce de frontera', description: 'Permite salir de Argentina y circular por países limítrofes.', billing: 'per_reservation', quantity: 0, maxQuantity: 1 },
  ];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService,
    private auth: AuthService
  ) {}

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.route.queryParamMap.subscribe((params) => {
      this.dias = +(params.get('dias') || 1);
      this.startAt = params.get('startAt') || '';
      this.endAt = params.get('endAt') || '';
      this.pickupLocationId = +(params.get('pickupLocationId') || 1);
      this.dropoffLocationId = +(params.get('dropoffLocationId') || 1);

      if (this.vehiculo) this.actualizarTotal();
    });

    this.cotizarService.obtenerVehiculoPorId(id).subscribe((v) => {
      if (!v) {
        this.router.navigate(['/cotizar']);
        return;
      }
      this.vehiculo = v;
      this.actualizarTotal();
      this.verificarDisponibilidad();
      this.cargarRatings();
    });
  }

  goBack(): void {
    this.router.navigate(['/cotizar/resultados'], {
      queryParams: {
        pickupLocationId: this.pickupLocationId,
        dropoffLocationId: this.dropoffLocationId,
        startAt: this.startAt,
        endAt: this.endAt,
      },
      queryParamsHandling: 'merge',
    });
  }

  private cargarRatings(): void {
    if (!this.vehiculo) return;

    this.ratingLoading = true;
    this.ratingError = '';

    this.cotizarService.getVehicleRatings(this.vehiculo.id, 6).subscribe({
      next: (r) => {
        this.ratingAvg = r.ratingAvg ?? null;
        this.ratingCount = r.ratingCount ?? 0;
        this.ratingItems = Array.isArray(r.items)
          ? r.items.map((it) => ({
              rating: it.rating ?? null,
              comment: it.comment ?? null,
              date: it.endAt ?? null,
            }))
          : [];
        this.ratingLoading = false;
      },
      error: () => {
        this.ratingLoading = false;
        this.ratingError = 'No se pudieron cargar las opiniones.';
      },
    });
  }

  get ratingAvgSafe(): number {
    return this.ratingAvg ?? 0;
  }

  get ratingAvgText(): string {
    if (this.ratingAvg == null) return '—';
    return this.ratingAvg.toFixed(1);
  }

  starsArray(n: number): number[] {
    return Array.from({ length: n }, (_, i) => i + 1);
  }

  get ratingBarPct(): number {
    if (!this.ratingCount || this.ratingAvg == null) return 0;
    const pct = (this.ratingAvg / 5) * 100;
    return Math.max(0, Math.min(100, pct));
  }

  private verificarDisponibilidad(): void {
    this.errorMsg = '';
    if (!this.vehiculo || !this.startAt || !this.endAt || !this.pickupLocationId) {
      this.unitsAvailable = undefined;
      return;
    }

    this.checking = true;
    this.cotizarService
      .checkAvailability({
        vehicleId: this.vehiculo.id,
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt,
      })
      .subscribe({
        next: (r) => {
          this.unitsAvailable = r.available ? 1 : 0;
          this.checking = false;
        },
        error: () => {
          this.unitsAvailable = undefined;
          this.errorMsg = 'No se pudo verificar la disponibilidad.';
          this.checking = false;
        },
      });
  }

  // -------------------- CÁLCULOS --------------------

  get baseAmount(): number {
    const veh = this.vehiculo;
    if (!veh) return 0;
    return (veh.dailyRate ?? 0) * this.dias;
  }

  // ✅ Seguro dinámico según días y dailyRate
  get seguroAmount(): number {
    if (!this.vehiculo || !this.selectedSeguroId) return 0;
    return this.seguroPriceFor(this.selectedSeguroId);
  }

  // ✅ Adicionales dinámicos según base, billing y días
  get adicionalesAmount(): number {
    const base = this.baseAmount;
    if (base <= 0) return 0;

    let total = 0;
    for (const a of this.adicionales) {
      if (a.quantity <= 0) continue;

      const perUnit = this.adicionalPriceFor(a.id); // ya considera billing/días
      total += perUnit * a.quantity;
    }
    return total;
  }

  // ✅ Precio total del seguro (según días)
  seguroPriceFor(id: string): number {
    const veh = this.vehiculo;
    if (!veh) return 0;

    const s = this.seguros.find((x) => x.id === id);
    if (!s) return 0;

    const daily = veh.dailyRate ?? 0;
    return daily * s.pctPerDay * this.dias;
  }

  // ✅ Precio por 1 unidad del adicional:
  // - per_day => 3% del base por día => base * 3% (ya incluye días)
  // - per_reservation => 3% del base total (1 vez)
  adicionalPriceFor(id: string): number {
    const base = this.baseAmount;
    if (base <= 0) return 0;

    const a = this.adicionales.find((x) => x.id === id);
    if (!a) return 0;

    const basePct = base * this.EXTRAS_PCT_OF_BASE;

    // Si es por día: usamos basePct (base ya incluye días, así que ya escala)
    // Si es por reserva: también basePct (1 vez). Si querés que NO escale por días, avisame y lo ajusto.
    // ---- Ajuste fino (recomendado):
    // per_day => (dailyRate * 3%) * días
    // per_reservation => (dailyRate * 3%) * 1
    // Para eso, calculo con dailyRate:
    const daily = (this.vehiculo?.dailyRate ?? 0);
    const perDayUnit = (daily * this.EXTRAS_PCT_OF_BASE) * this.dias; // escala por días
    const perReservationUnit = (daily * this.EXTRAS_PCT_OF_BASE);     // NO escala por días

    return a.billing === 'per_day' ? perDayUnit : perReservationUnit;
  }

  onSeguroChange(): void {
    this.actualizarTotal();
  }

  actualizarTotal(): void {
    this.total = this.baseAmount + this.seguroAmount + this.adicionalesAmount;
  }

  changeAdicionalCantidad(id: string, delta: number): void {
    const extra = this.adicionales.find((a) => a.id === id);
    if (!extra) return;

    extra.quantity = Math.min(extra.maxQuantity, Math.max(0, extra.quantity + delta));
    this.actualizarTotal();
  }

  get botonDeshabilitado(): boolean {
    const sinFechas = !this.startAt || !this.endAt;
    const sinCupo = this.unitsAvailable !== undefined && this.unitsAvailable <= 0;
    return sinFechas || sinCupo || this.checking || this.creating || !this.vehiculo;
  }

  // -------------------- PAYLOAD NUEVO (pricing) --------------------

  private buildPricingPayload(): {
    insuranceCode: string | null;
    extras: Array<{ code: string; quantity: number; price: number; billing: BillingType }>;
  } {
    const extras: Array<{ code: string; quantity: number; price: number; billing: BillingType }> = [];

    for (const a of this.adicionales) {
      if (a.quantity <= 0) continue;

      extras.push({
        code: a.id,
        quantity: a.quantity,
        price: this.adicionalPriceFor(a.id),
        billing: a.billing,
      });
    }

    return {
      insuranceCode: this.selectedSeguroId,
      extras,
    };
  }

  // -------------------- RESERVA --------------------

  private redirigirALogin(): void {
    const veh = this.vehiculo;
    if (!veh) return;

    const redirectUrl = this.router
      .createUrlTree(['/cotizar/detalle', veh.id], {
        queryParams: {
          dias: this.dias,
          startAt: this.startAt,
          endAt: this.endAt,
          pickupLocationId: this.pickupLocationId,
          dropoffLocationId: this.dropoffLocationId,
        },
      })
      .toString();

    const pendingPayload = {
      vehicleId: veh.id,
      pickupLocationId: this.pickupLocationId,
      dropoffLocationId: this.dropoffLocationId,
      startAt: this.startAt,
      endAt: this.endAt,
      pricing: this.buildPricingPayload(),
    };

    this.router.navigate(['/auth/login'], {
      queryParams: { redirectUrl },
      state: { pendingReservation: pendingPayload },
    });
  }

  confirmarReserva(): void {
    const veh = this.vehiculo;
    if (!veh) return;

    if (!this.startAt || !this.endAt) {
      alert('⚠️ Faltan las fechas de reserva.');
      return;
    }

    if (!this.selectedSeguroId) {
      alert('⚠️ Tenés que seleccionar un seguro para continuar.');
      return;
    }

    if (!this.auth.isLoggedIn()) {
      alert('🔐 Necesitás iniciar sesión para continuar con la reserva.');
      this.redirigirALogin();
      return;
    }

    this.verificarPerfilYCrear();
  }

  private verificarPerfilYCrear(): void {
    this.checking = true;
    this.errorMsg = '';

    this.auth.getProfile().subscribe({
      next: (profile) => {
        if (!profile.profileComplete) {
          this.checking = false;
          alert('Antes de confirmar la reserva tenés que completar tu perfil con tus datos personales y de licencia.');
          this.auth.setReturnUrl(this.router.url);
          this.router.navigate(['/perfil']);
          return;
        }
        this.crearReservaConDisponibilidad();
      },
      error: () => {
        this.checking = false;
        alert('No se pudo verificar tu perfil. Intentá nuevamente en unos minutos.');
      },
    });
  }

  private crearReservaConDisponibilidad(): void {
    const veh = this.vehiculo;
    if (!veh) return;

    this.checking = true;

    this.cotizarService
      .checkAvailability({
        vehicleId: veh.id,
        pickupLocationId: this.pickupLocationId,
        startAt: this.startAt,
        endAt: this.endAt,
      })
      .subscribe({
        next: (r) => {
          this.checking = false;

          if (!r.available) {
            this.unitsAvailable = 0;
            alert('❌ Sin stock en esas fechas.');
            return;
          }

          const payload = {
            vehicleId: veh.id,
            pickupLocationId: this.pickupLocationId,
            dropoffLocationId: this.dropoffLocationId,
            startAt: this.startAt,
            endAt: this.endAt,
            pricing: this.buildPricingPayload(),
          };

          this.creating = true;
          this.cotizarService.crearReserva(payload).subscribe({
            next: (res) => {
              this.creating = false;
              this.router.navigate(['/cotizar/confirmacion', res.id]);
            },
            error: (err) => {
              this.creating = false;

              if (err.status === 409) alert('❌ El vehículo no está disponible.');
              else if (err.status === 422) alert('⚠️ Datos inválidos (seguro obligatorio / pricing).');
              else if (err.status === 400) alert('⚠️ Fechas inválidas.');
              else if (err.status === 401 || err.status === 403) {
                alert('🔐 Necesitás iniciar sesión para continuar con la reserva.');
                this.redirigirALogin();
              } else {
                alert('💥 Error inesperado.');
              }
            },
          });
        },
        error: () => {
          this.checking = false;
          alert('No se pudo verificar la disponibilidad.');
        },
      });
  }
}