import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-ubicaciones',
  standalone: true,
  imports: [CommonModule],
  template: `
    <h2>Ubicaciones</h2>
    <p>ABM de sucursales + métricas (origen/destino más usado, reservas por sucursal).</p>
  `,
})
export class AdminUbicacionesComponent {}
