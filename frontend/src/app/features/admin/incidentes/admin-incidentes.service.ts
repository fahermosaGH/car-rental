import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';
import { Observable } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class AdminIncidentesService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  private headers(): HttpHeaders {
    return new HttpHeaders({
      Authorization: `Bearer ${this.auth.token}`
    });
  }

  list(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/admin/incidents`, {
      headers: this.headers()
    });
  }

  availableUnits(id: number): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/admin/incidents/${id}/available-units`, {
      headers: this.headers()
    });
  }

  reassign(id: number, newUnitId: number) {
    return this.http.post(
      `${this.apiUrl}/admin/incidents/${id}/reassign`,
      { newUnitId },
      { headers: this.headers() }
    );
  }
}