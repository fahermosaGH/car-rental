import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export interface AdminVehicleDto {
  id: number;
  brand: string | null;
  model: string | null;
  year: number | null;
  dailyPrice: number | null;
  isActive: boolean;
  imageUrl?: string | null;
}

export interface AdminVehicleCreateUpdate {
  brand?: string;
  model?: string;
  year?: number | null;
  dailyPrice?: number | null;
  isActive?: boolean;
  imageUrl?: string | null;
}

@Injectable({ providedIn: 'root' })
export class AdminVehiclesService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api/admin/vehicles';

  constructor(private http: HttpClient) {}

  list(includeInactive = false): Observable<AdminVehicleDto[]> {
    const qs = includeInactive ? '?includeInactive=1' : '';
    return this.http.get<AdminVehicleDto[]>(`${this.baseUrl}${qs}`);
  }

  create(payload: AdminVehicleCreateUpdate): Observable<AdminVehicleDto> {
    return this.http.post<AdminVehicleDto>(this.baseUrl, payload);
  }

  update(id: number, payload: AdminVehicleCreateUpdate): Observable<AdminVehicleDto> {
    return this.http.put<AdminVehicleDto>(`${this.baseUrl}/${id}`, payload);
  }

  deactivate(id: number): Observable<{ ok: boolean; id: number }> {
    return this.http.delete<{ ok: boolean; id: number }>(`${this.baseUrl}/${id}`);
  }
}
