import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';

export interface AdminLocation {
  id: number;
  name: string;
  city: string | null;
  address: string | null;
  latitude: number | null;
  longitude: number | null;
  isActive: boolean;
}

export interface AdminLocationCreate {
  name: string;
  address: string;
  city?: string | null;
  latitude?: number | null;
  longitude?: number | null;
  isActive?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminLocationsService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  private get headers(): HttpHeaders | undefined {
    const token = this.auth.token;
    return token ? new HttpHeaders({ Authorization: `Bearer ${token}` }) : undefined;
  }

  list(): Observable<AdminLocation[]> {
    return this.http.get<AdminLocation[]>(`${this.apiUrl}/admin/locations`, {
      headers: this.headers,
    });
  }

  create(payload: AdminLocationCreate): Observable<{ message: string; id: number }> {
    return this.http.post<{ message: string; id: number }>(
      `${this.apiUrl}/admin/locations`,
      payload,
      { headers: this.headers }
    );
  }

  update(id: number, payload: Partial<AdminLocationCreate>): Observable<{ message: string }> {
    return this.http.put<{ message: string }>(
      `${this.apiUrl}/admin/locations/${id}`,
      payload,
      { headers: this.headers }
    );
  }

  deactivate(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(
      `${this.apiUrl}/admin/locations/${id}`,
      { headers: this.headers }
    );
  }
}
