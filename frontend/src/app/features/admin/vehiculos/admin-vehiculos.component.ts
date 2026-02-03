import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-vehiculos',
  standalone: true,
  imports: [CommonModule],
  template: `
    <h2>Vehículos</h2>
    <p>ABM de vehículos + KPIs por vehículo (alquileres, rating promedio, ingresos simulados).</p>
  `,
})
export class AdminVehiculosComponent {}
