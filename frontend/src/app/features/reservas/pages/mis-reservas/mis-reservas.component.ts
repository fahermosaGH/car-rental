import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Router, RouterModule } from '@angular/router';
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
  status?: string;        // estado "crudo" de la API (pending / confirmed / cancelled)
  estadoLegible: string;  // Activa / Finalizada / Cancelada / Pendiente
}

type SortField = 'startAt' | 'endAt' | 'status';
type SortDirection = 'asc' | 'desc';

@Component({
  selector: 'app-mis-reservas',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './mis-reservas.component.html',
  styleUrls: ['./mis-reservas.component.css'],
})
export class MisReservasComponent implements OnInit {
  reservas: ReservaItem[] = [];
  loading = true;
  errorMsg = '';

  // orden actual
  sortField: SortField = 'startAt';
  sortDirection: SortDirection = 'desc';

  private apiUrl = environment.apiUrl;

  constructor(
    private http: HttpClient,
    private auth: AuthService,
    private router: Router
  ) {}

  ngOnInit(): void {
    console.log('[MisReservas] ngOnInit');
    this.cargarReservas();
  }

  private cargarReservas(): void {
    console.log('[MisReservas] cargarReservas()');
    this.loading = true;
    this.errorMsg = '';

    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    console.log('[MisReservas] headers', headers);

    this.http
      .get<any[]>(`${this.apiUrl}/my-reservations`, { headers })
      .subscribe({
        next: (data) => {
          console.log('[MisReservas] respuesta OK', data);

          this.reservas = data.map((r) => {
            const item: ReservaItem = {
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
              estadoLegible: '', // se completa abajo
            };

            item.estadoLegible = this.mapEstadoLegible(item);
            return item;
          });

          this.loading = false;
        },
        error: (err) => {
          console.log('[MisReservas] error', err);
          this.loading = false;
          this.errorMsg = 'No se pudieron cargar tus reservas.';
        },
      });
  }

  /** Mapea estado técnico + fechas -> texto amigable */
  private mapEstadoLegible(r: ReservaItem): string {
    const raw = (r.status || '').toLowerCase().trim();
    const hoy = new Date();
    const fin = r.endAt ? new Date(r.endAt) : null;

    if (raw === 'cancelled') return 'Cancelada';
    if (raw === 'pending') return 'Pendiente';

    if (fin && fin < hoy) {
      return 'Finalizada';
    }

    // por defecto, confirmed o cualquier otra cosa → Activa
    return 'Activa';
  }

  /** Lista ordenada según los criterios actuales */
  get reservasOrdenadas(): ReservaItem[] {
    const dir = this.sortDirection === 'asc' ? 1 : -1;

    return [...this.reservas].sort((a, b) => {
      switch (this.sortField) {
        case 'startAt': {
          const da = new Date(a.startAt).getTime();
          const db = new Date(b.startAt).getTime();
          return (da - db) * dir;
        }
        case 'endAt': {
          const da = new Date(a.endAt).getTime();
          const db = new Date(b.endAt).getTime();
          return (da - db) * dir;
        }
        case 'status': {
          // orden custom de estados
          const order = {
            'Activa': 1,
            'Pendiente': 2,
            'Finalizada': 3,
            'Cancelada': 4,
          } as const;

          const oa = order[a.estadoLegible as keyof typeof order] ?? 99;
          const ob = order[b.estadoLegible as keyof typeof order] ?? 99;
          return (oa - ob) * dir;
        }
        default:
          return 0;
      }
    });
  }

  /** Cambia el criterio de orden; si clickeás el mismo, invierte asc/desc */
  setSort(field: SortField): void {
    if (this.sortField === field) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortField = field;
      this.sortDirection = field === 'status' ? 'asc' : 'desc';
    }
  }

  /** Navega al detalle completo (pantalla de confirmación) */
  verDetalle(reserva: ReservaItem): void {
    this.router.navigate(['/cotizar/confirmacion', reserva.id]);
  }
}

