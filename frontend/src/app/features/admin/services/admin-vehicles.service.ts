import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../../environments/environment';

export interface AdminVehicleDto {
  id: number;
  brand: string | null;
  model: string | null;
  year: number | null;
  seats: number | null;
  transmission: string | null;
  categoryId: number | null;
  categoryName?: string | null;
  dailyPrice: number | null;
  isActive: boolean;
  imageUrl: string | null;
}

export interface AdminVehicleCreateUpdate {
  brand: string;
  model: string;
  year: number | null;
  seats: number | null;
  transmission: string | null;
  categoryId: number | null;
  dailyPrice: number | null;
  isActive: boolean;
  imageUrl?: string | null;
}

@Injectable({ providedIn: 'root' })
export class AdminVehiclesService {
  private readonly baseUrl: string;

  constructor(private http: HttpClient) {
    // ✅ Soporta apiUrl con o sin "/api"
    const root = (environment.apiUrl || '').replace(/\/+$/, '');
    const apiRoot = root.endsWith('/api') ? root : `${root}/api`;
    this.baseUrl = `${apiRoot}/admin/vehicles`;
  }

  list(includeInactive = false): Observable<AdminVehicleDto[]> {
    const url = includeInactive ? `${this.baseUrl}?includeInactive=1` : this.baseUrl;
    return this.http.get<AdminVehicleDto[]>(url);
  }

  create(payload: AdminVehicleCreateUpdate): Observable<AdminVehicleDto> {
    return this.http.post<AdminVehicleDto>(this.baseUrl, payload);
  }

  update(id: number, payload: AdminVehicleCreateUpdate): Observable<AdminVehicleDto> {
    return this.http.put<AdminVehicleDto>(`${this.baseUrl}/${id}`, payload);
  }

  deactivate(id: number): Observable<any> {
    return this.http.delete<any>(`${this.baseUrl}/${id}`);
  }
}