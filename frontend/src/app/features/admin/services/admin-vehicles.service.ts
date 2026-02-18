import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

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
  transmission: string;
  categoryId: number;
  dailyPrice: number | null;
  isActive: boolean;
  imageUrl: string | null;
}

@Injectable({ providedIn: 'root' })
export class AdminVehiclesService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api/admin/vehicles';

  constructor(private http: HttpClient) {}

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
