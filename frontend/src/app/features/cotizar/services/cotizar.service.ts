import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable, map } from 'rxjs';
import { VehicleOption } from '../models/quote';
import { environment } from '../../../../environments/environment';

@Injectable({ providedIn: 'root' })
export class CotizarService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  // 🔹 Obtener todos los vehículos desde el backend Symfony (catálogo general)
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
          description: `${v.brand} ${v.model} (${v.category?.name || v.category})`,
          // si algún día el back también manda unitsAvailable acá, lo tomamos
          unitsAvailable: typeof v.unitsAvailable === 'number' ? v.unitsAvailable : undefined
        }))
      )
    );
  }

  // 🔹 Obtener un vehículo por ID (sobre el catálogo ya cargado)
  obtenerVehiculoPorId(id: number): Observable<VehicleOption | undefined> {
    return this.buscarVehiculos().pipe(
      map((vehiculos) => vehiculos.find((v) => v.id === id))
    );
  }

  // 🔹 Sucursales reales
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

  // 🔹 Vehículos disponibles por sucursal + fechas (+ categoría opcional)
  getAvailableVehicles(params: {
    pickupLocationId: number;
    startAt: string; // 'YYYY-MM-DD' o ISO
    endAt: string;   // 'YYYY-MM-DD' o ISO
    category?: string; // <-- NUEVO (opcional)
  }): Observable<VehicleOption[]> {
    let httpParams = new HttpParams()
      .set('pickupLocationId', String(params.pickupLocationId))
      .set('startAt', params.startAt)
      .set('endAt', params.endAt);

    if (params.category && params.category.trim() !== '') {
      httpParams = httpParams.set('category', params.category.trim());
    }

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
            description: `${v.brand} ${v.model} (${v.category?.name || v.category})`,
            // 👇 lo que envía el back
            unitsAvailable: typeof v.unitsAvailable === 'number' ? v.unitsAvailable : undefined
          }))
        )
      );
  }

  // ✅ Chequear disponibilidad puntual para un vehículo en sucursal+fechas
  checkAvailability(params: {
    vehicleId: number;
    pickupLocationId: number;
    startAt: string; // YYYY-MM-DD o ISO
    endAt: string;   // YYYY-MM-DD o ISO
  }): Observable<{ available: boolean; message: string }> {
    const httpParams = new HttpParams()
      .set('vehicle', String(params.vehicleId))
      .set('pickup', String(params.pickupLocationId))
      .set('start', params.startAt)
      .set('end', params.endAt);

    return this.http.get<{ available: boolean; message: string }>(
      `${this.apiUrl}/check-availability`,
      { params: httpParams }
    );
  }

  // ✅ Crear reserva real en el backend
  crearReserva(payload: {
    vehicleId: number;
    pickupLocationId: number;
    dropoffLocationId: number;
    startAt: string;   // YYYY-MM-DD
    endAt: string;     // YYYY-MM-DD
    totalPrice: string | number;
    extras: Array<{ name: string; price: string | number }>;
  }): Observable<{ message: string; id: number }> {
    return this.http.post<{ message: string; id: number }>(
      `${this.apiUrl}/reservations`,
      payload
    );
  }
}

