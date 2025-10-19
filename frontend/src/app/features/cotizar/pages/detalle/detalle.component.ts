import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';
import { VehicleOption } from '../../models/quote';
import { HttpClient } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { environment } from '../../../../../environments/environment';

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

  extras = { seguro: false, silla: false, gps: false };
  preciosExtras = { seguro: 500, silla: 300, gps: 400 };

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private cotizarService: CotizarService,
    private http: HttpClient
  ) {}

  ngOnInit() {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.route.queryParamMap.subscribe(params => {
      this.dias = +(params.get('dias') || 1);
      this.startAt = params.get('startAt') || '';
      this.endAt = params.get('endAt') || '';
      this.pickupLocationId = +(params.get('pickupLocationId') || 1);
      this.dropoffLocationId = +(params.get('dropoffLocationId') || 1);

      console.log('📅 Fechas recibidas correctamente:', this.startAt, this.endAt);
    });

    this.cotizarService.obtenerVehiculoPorId(id).subscribe(v => {
      if (!v) {
        this.router.navigate(['/cotizar']);
        return;
      }
      this.vehiculo = v;
      this.actualizarTotal();
    });
  }

  actualizarTotal() {
    if (!this.vehiculo) return;
    let base = this.vehiculo.dailyRate * this.dias;
    if (this.extras.seguro) base += this.preciosExtras.seguro;
    if (this.extras.silla) base += this.preciosExtras.silla;
    if (this.extras.gps) base += this.preciosExtras.gps;
    this.total = base;
  }

  confirmarReserva() {
    if (!this.vehiculo) return;

    if (!this.startAt || !this.endAt) {
      alert('⚠️ Faltan las fechas de reserva.');
      return;
    }

    const extrasSeleccionados: any[] = [];
    if (this.extras.seguro) extrasSeleccionados.push({ name: 'Seguro', price: this.preciosExtras.seguro });
    if (this.extras.silla) extrasSeleccionados.push({ name: 'Silla para niño', price: this.preciosExtras.silla });
    if (this.extras.gps) extrasSeleccionados.push({ name: 'GPS', price: this.preciosExtras.gps });

    const payload = {
      vehicleId: this.vehiculo.id,
      pickupLocationId: this.pickupLocationId,
      dropoffLocationId: this.dropoffLocationId,
      startAt: this.startAt,
      endAt: this.endAt,
      totalPrice: this.total,
      extras: extrasSeleccionados
    };

    console.log('📦 Enviando reserva al backend:', payload);

    this.http.post(`${environment.apiUrl}/reservations`, payload).subscribe({
      next: (res) => {
        console.log('✅ Reserva creada correctamente:', res);
        alert('✅ Reserva creada correctamente!');
        this.router.navigate(['/cotizar']);
      },
      error: (err) => {
        console.error('❌ Error al crear reserva:', err);
        if (err.status === 409) alert('❌ El vehículo no está disponible en las fechas seleccionadas.');
        else if (err.status === 422) alert('⚠️ Datos faltantes o inválidos en la reserva.');
        else if (err.status === 400) alert('⚠️ Formato de solicitud incorrecto. Verifica las fechas.');
        else alert('💥 Error inesperado. Intenta de nuevo.');
      }
    });
  }
}
