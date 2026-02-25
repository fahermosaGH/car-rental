import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/services/auth.service';

export interface RankItem {
  label: string;
  value: number;
}

export interface AdminGeneralStats {
  users: number;
  vehicles: number;
  locations: number;
  reservationsTotal: number;
  reservationsActiveToday: number;
  cancellationsThisMonth: number;
  incomeThisMonth: number;

  unitsAvailable: number;
  unitsMaintenance: number;
  unitsInactive: number;

  // Rankings (mes)
  topVehicles: RankItem[];
  topPickupLocations: RankItem[];
  topCategories: RankItem[];
  incomeByLocation: RankItem[];
}

@Injectable({ providedIn: 'root' })
export class AdminStatsService {
  private apiUrl = environment.apiUrl; // ej: http://127.0.0.1:8000/api

  constructor(private http: HttpClient, private auth: AuthService) {}

  getGeneralStats(): Observable<AdminGeneralStats> {
    const token = this.auth.token;

    const headers = token
      ? new HttpHeaders({ Authorization: `Bearer ${token}` })
      : undefined;

    return this.http.get<AdminGeneralStats>(
      `${this.apiUrl}/admin/stats/general`,
      { headers }
    );
  }
}
