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
  rawStatus: string;      // estado "técnico": confirmed/cancelled
  estadoLegible: string;  // Activa / Finalizada / Cancelada / Pendiente
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

  // orden actual
  sortField: SortField = 'startAt';
  sortDirection: SortDirection = 'asc';

  // estado de cancelación
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

  // =========================
  // Carga de datos
  // =========================
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
            const estadoLegible = this.calcularEstadoLegible(
              rawStatus,
              r.startAt,
              r.endAt
            );
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
        error: (err) => {
          console.error('[MisReservas] error', err);
          this.loading = false;
          this.errorMsg = 'No se pudieron cargar tus reservas.';
        },
      });
  }

  private calcularDiasHastaInicio(startAt: string): number {
    const today = new Date();
    const start = new Date(startAt);

    // Normalizamos a medianoche
    today.setHours(0, 0, 0, 0);
    start.setHours(0, 0, 0, 0);

    const diffMs = start.getTime() - today.getTime();
    return Math.floor(diffMs / (1000 * 60 * 60 * 24));
  }

  private calcularEstadoLegible(
    rawStatus: string,
    startAt: string,
    endAt: string
  ): string {
    const base = rawStatus || 'confirmed';
    if (base === 'cancelled') return 'Cancelada';
    if (base === 'pending') return 'Pendiente';

    const hoy = new Date();
    const fin = new Date(endAt);

    if (fin.getTime() < hoy.getTime()) {
      return 'Finalizada';
    }

    return 'Activa';
  }

  private puedeCancelar(rawStatus: string, daysToStart: number): boolean {
    if (rawStatus === 'cancelled') return false;
    // Menos de 2 días → no se puede cancelar online
    if (daysToStart < 2) return false;
    return true;
  }

  // =========================
  // Ordenamiento
  // =========================
  setSort(field: SortField): void {
    if (this.sortField === field) {
      // mismo campo → toggle asc/desc
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

  // =========================
  // Acciones
  // =========================
  verDetalle(reserva: ReservaItem): void {
    // Reutilizamos /cotizar/confirmacion/:id como detalle de reserva
    this.router.navigate(['/cotizar/confirmacion', reserva.id]);
  }

  confirmarCancelacion(reserva: ReservaItem): void {
    if (!reserva.canCancel) {
      alert(
        'Esta reserva ya no puede cancelarse desde la web. ' +
          'Si necesitás ayuda, contactá a atención al cliente.'
      );
      return;
    }

    const masDe15Dias = reserva.daysToStart > 15;
    const cargoEstimado = !masDe15Dias
      ? Math.round(reserva.totalPrice * 0.2)
      : 0;

    let politicaLinea = '';
    if (masDe15Dias) {
      politicaLinea =
        'Política: la cancelación es sin cargo (reembolso total del importe abonado).';
    } else {
      politicaLinea =
        'Política: se aplicará un cargo del 20 % sobre el total de tu reserva.';
    }

    const mensaje =
      `Estás por cancelar la reserva N.º ${reserva.id}.\n\n` +
      `Vehículo: ${reserva.vehicleName}\n` +
      `Retiro: ${reserva.startAt} - ${reserva.pickupLocation}\n` +
      `Devolución: ${reserva.endAt} - ${reserva.dropoffLocation}\n\n` +
      `${politicaLinea}\n` +
      (!masDe15Dias
        ? `Cargo estimado: ARS ${cargoEstimado.toLocaleString('es-AR')}.\n\n`
        : '\n') +
      '¿Confirmás que querés cancelar la reserva?';

    const ok = window.confirm(mensaje);
    if (!ok) return;

    this.ejecutarCancelacion(reserva);
  }

  private ejecutarCancelacion(reserva: ReservaItem): void {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    this.cancelandoId = reserva.id;

    this.http
      .post<{
        message: string;
        status: string;
        penaltyPercent?: number;
        penaltyAmount?: string;
      }>(
        `${this.apiUrl}/reservations/${reserva.id}/cancel`,
        {},
        { headers }
      )
      .subscribe({
        next: (res) => {
          this.cancelandoId = null;

          reserva.rawStatus = res.status ?? 'cancelled';
          reserva.estadoLegible = 'Cancelada';
          reserva.canCancel = false;

          // refrescamos el orden por si cambia la posición
          this.aplicarOrden();

          alert(res.message || 'Reserva cancelada correctamente.');
        },
        error: (err) => {
          console.error('[MisReservas] cancelar error', err);
          this.cancelandoId = null;

          if (err.status === 422 || err.status === 409) {
            alert(err.error?.message || 'No se pudo cancelar la reserva.');
          } else if (err.status === 401) {
            alert(
              'Tu sesión expiró. Iniciá sesión nuevamente para gestionar reservas.'
            );
          } else {
            alert('Error inesperado al cancelar la reserva.');
          }
        },
      });
  }
}



