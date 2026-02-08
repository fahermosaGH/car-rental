import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';

export type ReservationStatus = 'pending' | 'confirmed' | 'cancelled';

export interface AdminReservationRow {
  id: number;
  status: ReservationStatus;
  startAt: string | null;
  endAt: string | null;
  totalPrice: number;
  userEmail: string | null;
  vehicle: string | null;
  pickupLocation: string | null;
  dropoffLocation: string | null;
}

export interface AdminReservationDetail {
  id: number;
  status: ReservationStatus;
  startAt: string | null;
  endAt: string | null;
  totalPrice: number;
  rating: number | null;
  ratingComment: string | null;

  user: { email: string; firstName: string; lastName: string } | null;
  vehicle: { id: number; brand: string | null; model: string | null; category: string | null } | null;

  pickupLocation: string | null;
  dropoffLocation: string | null;

  extras: { name: string; price: number }[];
}

@Injectable({ providedIn: 'root' })
export class AdminReservasService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  list(filters?: { status?: string; from?: string; to?: string }): Observable<AdminReservationRow[]> {
    let params = new HttpParams();
    if (filters?.status) params = params.set('status', filters.status);
    if (filters?.from) params = params.set('from', filters.from);
    if (filters?.to) params = params.set('to', filters.to);

    return this.http.get<AdminReservationRow[]>(`${this.apiUrl}/admin/reservations`, { params });
  }

  get(id: number): Observable<AdminReservationDetail> {
    return this.http.get<AdminReservationDetail>(`${this.apiUrl}/admin/reservations/${id}`);
  }

  updateStatus(id: number, status: ReservationStatus): Observable<{ message: string; id: number; status: string }> {
    return this.http.put<{ message: string; id: number; status: string }>(
      `${this.apiUrl}/admin/reservations/${id}/status`,
      { status }
    );
  }
}

