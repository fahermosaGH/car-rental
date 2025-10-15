import { Injectable } from '@angular/core';
import { Observable, of, delay, map } from 'rxjs';
import { VehicleOption } from '../models/quote';

@Injectable({ providedIn: 'root' })
export class CotizarService {
  private mock: VehicleOption[] = [
    { id: 1, category: 'Económico', name: 'Chevrolet Onix o similar', dailyRate: 32000, img: 'https://picsum.photos/seed/onix/400/220' },
    { id: 2, category: 'Compacto',  name: 'Volkswagen Polo o similar', dailyRate: 38500, img: 'https://picsum.photos/seed/polo/400/220' },
    { id: 3, category: 'SUV',       name: 'Chevrolet Tracker o similar', dailyRate: 55000, img: 'https://picsum.photos/seed/tracker/400/220' },
  ];

  buscarVehiculos(): Observable<VehicleOption[]> {
    return of(this.mock).pipe(delay(500));
  }

  obtenerVehiculoPorId(id: number): Observable<VehicleOption | undefined> {
    return of(this.mock).pipe(
      delay(200),
      map(lista => lista.find(v => v.id === id))
    );
  }
}
const mock: VehicleOption[] = [
  {
    id: 1,
    category: 'Económico',
    name: 'Chevrolet Onix o similar',
    dailyRate: 32000,
    img: 'https://picsum.photos/seed/onix/400/220',
    transmission: 'Manual',
    fuel: 'Nafta',
    description: 'Ideal para ciudad. Compacto, eficiente y fácil de estacionar.'
  },
  {
    id: 2,
    category: 'Compacto',
    name: 'Volkswagen Polo o similar',
    dailyRate: 38500,
    img: 'https://picsum.photos/seed/polo/400/220',
    transmission: 'Automática',
    fuel: 'Nafta',
    description: 'Un compacto moderno con gran confort y rendimiento.'
  },
  {
    id: 3,
    category: 'SUV',
    name: 'Chevrolet Tracker o similar',
    dailyRate: 55000,
    img: 'https://picsum.photos/seed/tracker/400/220',
    transmission: 'Automática',
    fuel: 'Nafta / Diesel',
    description: 'SUV familiar con amplio espacio y confort premium.'
  }
];
