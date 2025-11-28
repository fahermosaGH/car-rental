import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { FormsModule } from '@angular/forms';

import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';
import { AuthService } from '../../../../core/services/auth.service'; // ✅ ruta corregida (3 niveles)

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

  // extras
  extras = { seguro: false, silla: false, gps: false };
  preciosExtras = { seguro: 500, silla: 300, gps: 400 };

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService,
    private auth: AuthService
  ) {}

  ngOnInit() {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.route.queryParamMap.subscribe(params => {
      this.dias = +(params.get('dias') || 1);
      this.startAt = params.get('startAt') || '';
      this.endAt   = params.get('endAt') || '';
      this.pickupLocationId  = +(params.get('pickupLocationId') || 1);
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

  actualizarTotal() {
    if (!this.vehiculo) return;
    let base = this.vehiculo.dailyRate * this.dias;
    if (this.extras.seguro) base += this.preciosExtras.seguro;
    if (this.extras.silla)  base += this.preciosExtras.silla;
    if (this.extras.gps)    base += this.preciosExtras.gps;
    this.total = base;
  }

  get botonDeshabilitado(): boolean {
    const sinFechas = !this.startAt || !this.endAt;
    const sinCupo   = this.unitsAvailable !== undefined && this.unitsAvailable <= 0;
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
      extras: [
        ...(this.extras.seguro ? [{ name: 'Seguro',          price: this.preciosExtras.seguro }] : []),
        ...(this.extras.silla  ? [{ name: 'Silla para niño', price: this.preciosExtras.silla  }] : []),
        ...(this.extras.gps    ? [{ name: 'GPS',             price: this.preciosExtras.gps    }] : []),
      ],
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

    // 🔑 1) Si NO está logueado, no pegamos al backend: avisamos y lo mandamos a login
    if (!this.auth.isLoggedIn()) {
      alert('🔐 Necesitás iniciar sesión para continuar con la reserva.');
      this.redirigirALogin();
      return;
    }

    // 2) Revalidar disponibilidad
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

        // 3) Crear reserva
        const extrasSeleccionados: Array<{name: string; price: number}> = [];
        if (this.extras.seguro) extrasSeleccionados.push({ name: 'Seguro',          price: this.preciosExtras.seguro });
        if (this.extras.silla)  extrasSeleccionados.push({ name: 'Silla para niño', price: this.preciosExtras.silla  });
        if (this.extras.gps)    extrasSeleccionados.push({ name: 'GPS',             price: this.preciosExtras.gps    });

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
            alert('✅ Reserva creada correctamente (ID ' + res.id + ').');
            this.router.navigate(['/cotizar']);
          },
          error: (err) => {
            this.creating = false;

            if (err.status === 409)       alert('❌ El vehículo no está disponible.');
            else if (err.status === 422)  alert('⚠️ Datos inválidos.');
            else if (err.status === 400)  alert('⚠️ Fechas inválidas.');
            else if (err.status === 401 || err.status === 403) {
              // Sesión inválida o no logueado (backup si el token expiró en el medio)
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

