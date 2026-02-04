import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';

export interface AdminGeneralStats {
  users: number;
  vehicles: number;
  locations: number;
  reservationsTotal: number;
  reservationsActiveToday: number;
  cancellationsThisMonth: number;
  incomeThisMonth: number;
}

@Injectable({ providedIn: 'root' })
export class AdminStatsService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient, private auth: AuthService) {}

  getGeneralStats(): Observable<AdminGeneralStats> {
    const headers = this.auth.token
      ? new HttpHeaders({ Authorization: `Bearer ${this.auth.token}` })
      : undefined;

    return this.http.get<AdminGeneralStats>(
      `${this.apiUrl}/admin/stats/general`,
      { headers }
    );
  }
}
