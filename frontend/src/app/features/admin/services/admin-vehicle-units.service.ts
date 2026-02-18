import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';

export type VehicleUnitStatus = 'available' | 'maintenance' | 'inactive';

export interface VehicleUnitRowDto {
  id: number;
  plate: string;
  status: VehicleUnitStatus;
  vehicle: { id: number; brand: string | null; model: string | null; year: number | null } | null;
  location: { id: number; name: string; city: string | null } | null;
}

export interface VehicleUnitCreatePayload {
  vehicleId: number;
  locationId: number;
  plate: string;
  status?: VehicleUnitStatus; // default available
}

export interface VehicleUnitUpdatePayload {
  vehicleId?: number;
  locationId?: number;
  plate?: string;
  status?: VehicleUnitStatus;
}

@Injectable({ providedIn: 'root' })
export class AdminVehicleUnitsService {
  private readonly baseUrl = 'http://127.0.0.1:8000/api/admin/vehicle-units';

  constructor(private http: HttpClient) {}

  list(params?: { vehicleId?: number; locationId?: number; status?: VehicleUnitStatus }): Observable<VehicleUnitRowDto[]> {
    const q: string[] = [];
    if (params?.vehicleId) q.push(`vehicleId=${params.vehicleId}`);
    if (params?.locationId) q.push(`locationId=${params.locationId}`);
    if (params?.status) q.push(`status=${params.status}`);
    const url = q.length ? `${this.baseUrl}?${q.join('&')}` : this.baseUrl;
    return this.http.get<VehicleUnitRowDto[]>(url);
  }

  create(payload: VehicleUnitCreatePayload): Observable<any> {
    return this.http.post<any>(this.baseUrl, payload);
  }

  update(id: number, payload: VehicleUnitUpdatePayload): Observable<any> {
    return this.http.put<any>(`${this.baseUrl}/${id}`, payload);
  }

  delete(id: number): Observable<any> {
    return this.http.delete<any>(`${this.baseUrl}/${id}`);
  }
}
