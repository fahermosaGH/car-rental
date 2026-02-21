import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { AdminIncidentesService } from './admin-incidentes.service';

@Component({
  selector: 'app-admin-incidentes',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './admin-incidentes.component.html',
  styleUrls: ['./admin-incidentes.component.css']
})
export class AdminIncidentesComponent implements OnInit {

  rows: any[] = [];
  unitsMap: Record<number, any[]> = {};
  selectedUnit: Record<number, number | null> = {};
  loadingUnits: Record<number, boolean> = {};

  constructor(private api: AdminIncidentesService) {}

  ngOnInit(): void {
    this.cargar();
  }

  cargar() {
    this.api.list().subscribe(data => {
      this.rows = data ?? [];
    });
  }

  isResolved(it: any): boolean {
    return (it?.status === 'resolved');
  }

  fmtDateTime(s: string | null | undefined): string {
    if (!s) return '-';
    const d = new Date(s);
    if (isNaN(d.getTime())) return s;
    return d.toLocaleString();
  }

  cargarUnidades(id: number) {
    this.loadingUnits[id] = true;

    this.api.availableUnits(id).subscribe({
      next: data => {
        this.unitsMap[id] = data ?? [];
        this.selectedUnit[id] = null;
        this.loadingUnits[id] = false;
      },
      error: () => {
        this.unitsMap[id] = [];
        this.selectedUnit[id] = null;
        this.loadingUnits[id] = false;
      }
    });
  }

  reasignar(id: number) {
    const unitId = this.selectedUnit[id];

    if (!unitId) {
      alert('Seleccioná una unidad válida.');
      return;
    }

    this.api.reassign(id, unitId).subscribe({
      next: () => {
        alert('Unidad reasignada correctamente.');
        this.cargar();
        this.unitsMap[id] = [];
      },
      error: (err) => {
        alert(err?.error?.error ?? 'Error al reasignar.');
      }
    });
  }
}