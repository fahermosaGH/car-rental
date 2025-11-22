import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../../../environments/environment';
import { AuthService } from '../../../../core/services/auth.service';

interface ReservaItem {
  id: number;
  vehicleName: string;
  pickupLocation: string;
  dropoffLocation: string;
  startAt: string;
  endAt: string;
  totalPrice: number;
  status?: string;
}

@Component({
  selector: 'app-mis-reservas',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mis-reservas.component.html',
  styleUrls: ['./mis-reservas.component.css'],
})
export class MisReservasComponent implements OnInit {
  reservas: ReservaItem[] = [];
  loading = true;
  errorMsg = '';

  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  ngOnInit(): void {
    this.cargarReservas();
  }

  private cargarReservas(): void {
    this.loading = true;
    this.errorMsg = '';

    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    this.http
      .get<any[]>(`${this.apiUrl}/my-reservations`, { headers })
      .subscribe({
        next: (data) => {
          this.reservas = data.map((r) => ({
            id: r.id,
            vehicleName:
              r.vehicleName ??
              `${r.vehicle?.brand ?? ''} ${r.vehicle?.model ?? ''}`.trim(),
            pickupLocation:
              r.pickupLocationName ??
              r.pickupLocation?.name ??
              'Sucursal origen',
            dropoffLocation:
              r.dropoffLocationName ??
              r.dropoffLocation?.name ??
              'Sucursal destino',
            startAt: r.startAt,
            endAt: r.endAt,
            totalPrice: Number(r.totalPrice ?? 0),
            status: r.status,
          }));
          this.loading = false;
        },
        error: () => {
          this.loading = false;
          this.errorMsg = 'No se pudieron cargar tus reservas.';
        },
      });
  }
}

