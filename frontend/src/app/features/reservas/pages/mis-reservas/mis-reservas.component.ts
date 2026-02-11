import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router } from '@angular/router';
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
  rawStatus: string;
  estadoLegible: string;
  daysToStart: number;
  canCancel: boolean;
}

type SortField = 'startAt' | 'endAt' | 'status';
type SortDirection = 'asc' | 'desc';

@Component({
  selector: 'app-mis-reservas',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './mis-reservas.component.html',
  styleUrls: ['./mis-reservas.component.css'],
})
export class MisReservasComponent implements OnInit {
  reservas: ReservaItem[] = [];
  reservasOrdenadas: ReservaItem[] = [];

  loading = true;
  errorMsg = '';

  sortField: SortField = 'startAt';
  sortDirection: SortDirection = 'asc';

  cancelandoId: number | null = null;
  private apiUrl = environment.apiUrl;

  constructor(
    private http: HttpClient,
    private auth: AuthService,
    private router: Router
  ) {}

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
          this.reservas = data.map((r) => {
            const rawStatus: string = r.status ?? 'confirmed';
            const daysToStart = this.calcularDiasHastaInicio(r.startAt);

            const estadoLegible = this.calcularEstadoLegible(rawStatus);
            const canCancel = this.puedeCancelar(rawStatus, daysToStart);

            return {
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
              rawStatus,
              estadoLegible,
              daysToStart,
              canCancel,
            };
          });

          this.aplicarOrden();
          this.loading = false;
        },
        error: () => {
          this.errorMsg = 'No se pudieron cargar tus reservas.';
          this.loading = false;
        },
      });
  }

  private calcularDiasHastaInicio(startAt: string): number {
    const today = new Date();
    const start = new Date(startAt);
    today.setHours(0, 0, 0, 0);
    start.setHours(0, 0, 0, 0);

    const diffMs = start.getTime() - today.getTime();
    return Math.floor(diffMs / (1000 * 60 * 60 * 24));
  }

  // 🔥 CORRECCIÓN CLAVE AQUÍ
  private calcularEstadoLegible(rawStatus: string): string {
    switch (rawStatus) {
      case 'cancelled':
        return 'Cancelada';
      case 'pending':
        return 'Pendiente';
      case 'completed':
        return 'Finalizada';
      case 'confirmed':
        return 'Activa';
      default:
        return 'Activa';
    }
  }

  private puedeCancelar(rawStatus: string, daysToStart: number): boolean {
    if (rawStatus !== 'confirmed') return false;
    if (daysToStart < 2) return false;
    return true;
  }

  setSort(field: SortField): void {
    if (this.sortField === field) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortField = field;
      this.sortDirection = 'asc';
    }
    this.aplicarOrden();
  }

  private aplicarOrden(): void {
    const dir = this.sortDirection === 'asc' ? 1 : -1;

    this.reservasOrdenadas = [...this.reservas].sort((a, b) => {
      let va: any;
      let vb: any;

      switch (this.sortField) {
        case 'startAt':
          va = new Date(a.startAt).getTime();
          vb = new Date(b.startAt).getTime();
          break;
        case 'endAt':
          va = new Date(a.endAt).getTime();
          vb = new Date(b.endAt).getTime();
          break;
        case 'status':
          va = a.estadoLegible;
          vb = b.estadoLegible;
          break;
      }

      if (va < vb) return -1 * dir;
      if (va > vb) return 1 * dir;
      return 0;
    });
  }

  verDetalle(reserva: ReservaItem): void {
    this.router.navigate(['/cotizar/confirmacion', reserva.id], {
      queryParams: {
        estado: reserva.estadoLegible
      }
    });
  }

  confirmarCancelacion(reserva: ReservaItem): void {
    if (!reserva.canCancel) {
      alert('Esta reserva ya no puede cancelarse.');
      return;
    }

    if (!confirm(`¿Cancelar la reserva #${reserva.id}?`)) return;

    this.ejecutarCancelacion(reserva);
  }

  private ejecutarCancelacion(reserva: ReservaItem): void {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    this.cancelandoId = reserva.id;

    this.http
      .post(`${this.apiUrl}/reservations/${reserva.id}/cancel`, {}, { headers })
      .subscribe({
        next: () => {
          reserva.rawStatus = 'cancelled';
          reserva.estadoLegible = 'Cancelada';
          reserva.canCancel = false;
          this.aplicarOrden();
          this.cancelandoId = null;
        },
        error: () => {
          this.cancelandoId = null;
          alert('No se pudo cancelar la reserva.');
        },
      });
  }
}
