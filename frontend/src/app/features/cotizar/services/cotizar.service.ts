import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
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
          category: v.category,
          name: `${v.brand} ${v.model}`,
          dailyRate: parseFloat(v.dailyRate),
          img: 'https://picsum.photos/seed/' + v.model + '/400/220',
          transmission: v.transmission,
          fuel: 'Nafta',
          description: `${v.brand} ${v.model} (${v.category})`
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
}