import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { VehicleOption } from '../models/quote';
import { environment } from '../../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CotizarService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // 🔹 Obtener todos los vehículos desde el backend Symfony
  buscarVehiculos(): Observable<VehicleOption[]> {
    return this.http.get<any[]>(`${this.apiUrl}/vehicles`).pipe(
      map((data) =>
        data.map((v) => ({
          id: v.id,
          category: v.category?.name || v.category || 'Sin categoría',
          name: `${v.brand} ${v.model}`,
          dailyRate: parseFloat(v.dailyPriceOverride || v.dailyRate || 0),
          img: 'https://picsum.photos/seed/' + v.model + '/400/220',
          transmission: v.transmission,
          fuel: 'Nafta',
          description: `${v.brand} ${v.model} (${v.category?.name || v.category})`
        }))
      )
    );
  }

  // 🔹 Obtener un vehículo por ID
  obtenerVehiculoPorId(id: number): Observable<VehicleOption | undefined> {
    return this.buscarVehiculos().pipe(
      map((vehiculos) => vehiculos.find((v) => v.id === id))
    );
  }

  // 🆕 Nuevo: obtener sucursales reales desde el backend
  obtenerSucursales(): Observable<any[]> {
    return this.http.get<any[]>(`${this.apiUrl}/locations`).pipe(
      map((data) =>
        data.map((s) => ({
          id: s.id,
          nombre: s.name || s.nombre,
          ciudad: s.city || '',
          direccion: s.address || ''
        }))
      )
    );
  }

  // 🆕 Nuevo: vehículos disponibles por sucursal + fechas
  getAvailableVehicles(params: {
    pickupLocationId: number;
    startAt: string; // 'YYYY-MM-DD' o ISO
    endAt: string;   // 'YYYY-MM-DD' o ISO
  }): Observable<VehicleOption[]> {
    const httpParams = new HttpParams()
      .set('pickupLocationId', String(params.pickupLocationId))
      .set('startAt', params.startAt)
      .set('endAt', params.endAt);

    return this.http
      .get<any[]>(`${this.apiUrl}/vehicles/available`, { params: httpParams })
      .pipe(
        map((data) =>
          data.map((v) => ({
            id: v.id,
            category: v.category?.name || v.category || 'Sin categoría',
            name: `${v.brand} ${v.model}`,
            dailyRate: parseFloat(v.dailyPriceOverride || v.dailyRate || 0),
            img: 'https://picsum.photos/seed/' + v.model + '/400/220',
            transmission: v.transmission,
            fuel: 'Nafta',
            description: `${v.brand} ${v.model} (${v.category?.name || v.category})`
          }))
        )
      );
  }
}
