import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-admin-reservas',
  standalone: true,
  imports: [CommonModule],
  template: `
    <h2>Reservas</h2>
    <p>Listado + filtros (cliente, fechas, vehículo, sucursal) y acciones (confirmar/cancelar).</p>
  `,
})
export class AdminReservasComponent {}
