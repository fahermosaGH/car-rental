import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';
import { Observable } from 'rxjs';

export type IncidentStatus = 'open' | 'resolved' | 'cancelled';

export interface AdminIncidentRow {
  id: number;
  status: IncidentStatus;
  description: string;
  createdAt: string;

  reservation: {
    id: number;
    status: string;
    startAt: string;
    endAt: string;
    pickupLocationName?: string | null;
    dropoffLocationName?: string | null;
    vehicleName?: string | null;
    vehicleId?: number | null;
    locationId?: number | null;
    vehicleUnitPlate?: string | null;
  };

  damagedUnit?: {
    id: number;
    plate: string;
    status: string;
  } | null;
}

@Injectable({ providedIn: 'root' })
export class AdminIncidentesService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  private authHeaders(): HttpHeaders | undefined {
    return this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;
  }

  list(params?: { status?: string }): Observable<AdminIncidentRow[]> {
    let httpParams = new HttpParams();
    if (params?.status) httpParams = httpParams.set('status', params.status);

    return this.http.get<AdminIncidentRow[]>(
      `${this.apiUrl}/admin/incidents`,
      { headers: this.authHeaders(), params: httpParams }
    );
  }

  resolve(id: number): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/admin/incidents/${id}/resolve`,
      {},
      { headers: this.authHeaders() }
    );
  }

  reassign(
    id: number,
    payload: { newUnitId: number; markDamagedAsMaintenance?: boolean }
  ): Observable<any> {
    return this.http.post(
      `${this.apiUrl}/admin/incidents/${id}/reassign`,
      payload,
      { headers: this.authHeaders() }
    );
  }
}