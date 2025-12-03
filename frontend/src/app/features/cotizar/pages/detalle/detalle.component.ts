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
  price: number; // por reserva
}

interface AdicionalOption {
  id: string;
  label: string;
  description: string;
  price: number;       // precio unitario
  billing: BillingType;
  quantity: number;    // cantidad seleccionada
  maxQuantity: number; // tope
}

@Component({
  selector: 'app-detalle',
  standalone: true,
  imports: [CommonModule, RouterModule, FormsModule],
  templateUrl: './detalle.component.html',
  styleUrls: ['./detalle.component.css']
})
export class DetalleComponent implements OnInit {
  vehiculo?: VehicleOption;

  dias = 1;
  total = 0;

  startAt = '';
  endAt = '';
  pickupLocationId = 1;
  dropoffLocationId = 1;

  // disponibilidad
  unitsAvailable?: number;
  checking = false;
  creating = false;
  errorMsg = '';

  // --- Seguros estilo Hertz (precio por reserva) ---
  seguros: SeguroOption[] = [
    {
      id: 'smart',
      label: 'SMART COVER',
      description: 'Cobertura que reduce 100% la franquicia por daños de colisión y vuelco.',
      price: 9000
    },
    {
      id: 'plus',
      label: 'PLUS COVER',
      description: 'Cobertura que reduce 100% la franquicia por daños de colisión.',
      price: 6500
    },
    {
      id: 'tyres',
      label: 'CUBIERTAS COVER',
      description: 'Cobertura adicional que reduce 100% la franquicia por daños y roturas de cubiertas.',
      price: 3800
    }
  ];

  selectedSeguroId: string | null = null;

  // --- Adicionales estilo Hertz ---
  adicionales: AdicionalOption[] = [
    {
      id: 'booster',
      label: 'Booster (4–10 años)',
      description: 'Silla especial para niños de 4 a 10 años.',
      price: 2800,
      billing: 'per_day',
      quantity: 0,
      maxQuantity: 2
    },
    {
      id: 'young_driver',
      label: 'Conductor joven',
      description: 'Si tenés entre 18 y 20 años podés alquilar un auto. Servicio con cargo adicional.',
      price: 4500,
      billing: 'per_day',
      quantity: 0,
      maxQuantity: 1
    },
    {
      id: 'additional_driver',
      label: 'Conductor adicional',
      description: 'Persona autorizada para conducir el vehículo aparte del conductor principal.',
      price: 3200,
      billing: 'per_day',
      quantity: 0,
      maxQuantity: 2
    },
    {
      id: 'baby_seat',
      label: 'Silla de bebé (1–3 años)',
      description: 'Silla especial para bebés entre 1 y 3 años. Sujeto a disponibilidad.',
      price: 2800,
      billing: 'per_day',
      quantity: 0,
      maxQuantity: 2
    },
    {
      id: 'border_cross',
      label: 'Cruce de frontera',
      description: 'Permite salir de Argentina y circular por países limítrofes. Requiere autorización previa.',
      price: 30000,
      billing: 'per_reservation',
      quantity: 0,
      maxQuantity: 1
    }
  ];

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService,
    private auth: AuthService
  ) { }

  ngOnInit() {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.route.queryParamMap.subscribe(params => {
      this.dias = +(params.get('dias') || 1);
      this.startAt = params.get('startAt') || '';
      this.endAt = params.get('endAt') || '';
      this.pickupLocationId = +(params.get('pickupLocationId') || 1);
      this.dropoffLocationId = +(params.get('dropoffLocationId') || 1);

      if (this.vehiculo) this.actualizarTotal();
    });

    this.cotizarService.obtenerVehiculoPorId(id).subscribe(v => {
      if (!v) {
        this.router.navigate(['/cotizar']);
        return;
      }
      this.vehiculo = v;
      this.actualizarTotal();
      this.verificarDisponibilidad();
    });
  }

  private verificarDisponibilidad() {
    this.errorMsg = '';
    if (!this.vehiculo || !this.startAt || !this.endAt || !this.pickupLocationId) {
      this.unitsAvailable = undefined;
      return;
    }

    this.checking = true;
    this.cotizarService.checkAvailability({
      vehicleId: this.vehiculo!.id,
      pickupLocationId: this.pickupLocationId,
      startAt: this.startAt,
      endAt: this.endAt
    }).subscribe({
      next: (r) => {
        this.unitsAvailable = r.available ? 1 : 0;
        this.checking = false;
      },
      error: () => {
        this.unitsAvailable = undefined;
        this.errorMsg = 'No se pudo verificar la disponibilidad.';
        this.checking = false;
      }
    });
  }

  // --- Helpers de cálculo ---

  get baseAmount(): number {
    if (!this.vehiculo) return 0;
    return this.vehiculo.dailyRate * this.dias;
  }

  get seguroAmount(): number {
    return this.getSeguroTotal();
  }

  get adicionalesAmount(): number {
    return this.getAdicionalesTotal();
  }

  private getSeguroTotal(): number {
    if (!this.selectedSeguroId) return 0;
    const s = this.seguros.find(x => x.id === this.selectedSeguroId);
    return s ? s.price : 0;
  }

  private getAdicionalesTotal(): number {
    let total = 0;
    for (const a of this.adicionales) {
      if (a.quantity <= 0) continue;
      if (a.billing === 'per_day') {
        total += a.price * a.quantity * this.dias;
      } else {
        total += a.price * a.quantity;
      }
    }
    return total;
  }

  private buildExtrasPayload(): Array<{ name: string; price: number }> {
    const extras: Array<{ name: string; price: number }> = [];

    if (this.selectedSeguroId) {
      const s = this.seguros.find(x => x.id === this.selectedSeguroId);
      if (s) {
        extras.push({
          name: s.label,
          price: this.getSeguroTotal()
        });
      }
    }

    for (const a of this.adicionales) {
      if (a.quantity <= 0) continue;
      const totalPrice =
        a.billing === 'per_day'
          ? a.price * a.quantity * this.dias
          : a.price * a.quantity;

      extras.push({
        name: a.label,
        price: totalPrice
      });
    }

    return extras;
  }

  actualizarTotal() {
    if (!this.vehiculo) return;
    this.total = this.baseAmount + this.getSeguroTotal() + this.getAdicionalesTotal();
  }

  changeAdicionalCantidad(id: string, delta: number) {
    const extra = this.adicionales.find(a => a.id === id);
    if (!extra) return;
    const nuevaCantidad = Math.min(
      extra.maxQuantity,
      Math.max(0, extra.quantity + delta)
    );
    extra.quantity = nuevaCantidad;
    this.actualizarTotal();
  }

  get botonDeshabilitado(): boolean {
    const sinFechas = !this.startAt || !this.endAt;
    const sinCupo = this.unitsAvailable !== undefined && this.unitsAvailable <= 0;
    return sinFechas || sinCupo || this.checking || this.creating || !this.vehiculo;
  }

  private redirigirALogin() {
    if (!this.vehiculo) return;

    const redirectUrl = this.router.createUrlTree(
      ['/cotizar/detalle', this.vehiculo.id],
      {
        queryParams: {
          dias: this.dias,
          startAt: this.startAt,
          endAt: this.endAt,
          pickupLocationId: this.pickupLocationId,
          dropoffLocationId: this.dropoffLocationId
        }
      }
    ).toString();

    const pendingPayload = {
      vehicleId: this.vehiculo.id,
      pickupLocationId: this.pickupLocationId,
      dropoffLocationId: this.dropoffLocationId,
      startAt: this.startAt,
      endAt: this.endAt,
      totalPrice: this.total,
      extras: this.buildExtrasPayload()
    };

    this.router.navigate(
      ['/auth/login'],
      {
        queryParams: { redirectUrl },
        state: { pendingReservation: pendingPayload }
      }
    );
  }

  confirmarReserva() {
    if (!this.vehiculo) return;

    if (!this.startAt || !this.endAt) {
      alert('⚠️ Faltan las fechas de reserva.');
      return;
    }

    // 1) si no está logueado → mismo flujo de antes
    if (!this.auth.isLoggedIn()) {
      alert('🔐 Necesitás iniciar sesión para continuar con la reserva.');
      this.redirigirALogin();
      return;
    }

    // 2) si está logueado → verificamos que el perfil esté completo
    this.verificarPerfilYCrear();
  }

  /** Verifica que el perfil esté completo antes de crear la reserva */
  private verificarPerfilYCrear() {
    this.checking = true;
    this.errorMsg = '';

    this.auth.getProfile().subscribe({
      next: (profile) => {
        if (!profile.profileComplete) {
          this.checking = false;
          alert(
            'Antes de confirmar la reserva tenés que completar tu perfil con tus datos personales y de licencia.'
          );

          // Lo mandamos a /perfil y dejamos la URL actual como returnUrl
          this.auth.setReturnUrl(this.router.url);
          this.router.navigate(['/perfil']);
          return;
        }

        // Perfil OK → seguimos con el flujo normal de disponibilidad + creación
        this.crearReservaConDisponibilidad();
      },
      error: () => {
        this.checking = false;
        alert('No se pudo verificar tu perfil. Intentá nuevamente en unos minutos.');
      }
    });
  }

  /** Lógica original de checkAvailability + crearReserva extraída a un método aparte */
  private crearReservaConDisponibilidad() {
    if (!this.vehiculo) return;

    this.checking = true;
    this.cotizarService.checkAvailability({
      vehicleId: this.vehiculo!.id,
      pickupLocationId: this.pickupLocationId,
      startAt: this.startAt,
      endAt: this.endAt
    }).subscribe({
      next: (r) => {
        this.checking = false;
        if (!r.available) {
          this.unitsAvailable = 0;
          alert('❌ Sin stock en esas fechas.');
          return;
        }

        const extrasSeleccionados = this.buildExtrasPayload();

        const payload = {
          vehicleId: this.vehiculo!.id,
          pickupLocationId: this.pickupLocationId,
          dropoffLocationId: this.dropoffLocationId,
          startAt: this.startAt,
          endAt: this.endAt,
          totalPrice: this.total,
          extras: extrasSeleccionados
        };

        this.creating = true;
        this.cotizarService.crearReserva(payload).subscribe({
          next: (res) => {
            this.creating = false;

            // Ahora vamos a la pantalla de confirmación REAL
            this.router.navigate(
              ['/cotizar/confirmacion', res.id]
            );
          },

          error: (err) => {
            this.creating = false;

            if (err.status === 409) alert('❌ El vehículo no está disponible.');
            else if (err.status === 422) alert('⚠️ Datos inválidos.');
            else if (err.status === 400) alert('⚠️ Fechas inválidas.');
            else if (err.status === 401 || err.status === 403) {
              alert('🔐 Necesitás iniciar sesión para continuar con la reserva.');
              this.redirigirALogin();
            } else {
              alert('💥 Error inesperado.');
            }
          }
        });
      },
      error: () => {
        this.checking = false;
        alert('No se pudo verificar la disponibilidad.');
      }
    });
  }
}
