import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';

export type ReservationStatus = 'pending' | 'confirmed' | 'completed' | 'cancelled';

export type AdminReservationRow = {
  id: number;
  userFullName?: string | null;
  userEmail?: string | null;
  vehicle?: string | null;
  pickupLocation?: string | null;
  dropoffLocation?: string | null;
  startAt?: string | null;
  endAt?: string | null;
  totalPrice?: number | string | null;
  status: ReservationStatus;
};

@Injectable({ providedIn: 'root' })
export class AdminReservasService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  private get headers(): HttpHeaders | undefined {
    return this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;
  }

  list(filters?: {
    status?: ReservationStatus;
    from?: string;
    to?: string;
  }): Observable<AdminReservationRow[]> {
    let params = new HttpParams();
    if (filters?.status) params = params.set('status', filters.status);
    if (filters?.from) params = params.set('from', filters.from);
    if (filters?.to) params = params.set('to', filters.to);

    return this.http.get<AdminReservationRow[]>(`${this.apiUrl}/admin/reservations`, {
      params,
      headers: this.headers,
    });
  }

  updateStatus(id: number, status: ReservationStatus): Observable<any> {
    return this.http.patch(
      `${this.apiUrl}/admin/reservations/${id}/status`,
      { status },
      { headers: this.headers }
    );
  }
}