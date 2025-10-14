import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';

@Component({
  selector: 'app-buscador',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './buscador.component.html',
  styleUrls: ['./buscador.component.css']
})
export class BuscadorComponent {
  private fb = inject(FormBuilder);
  private router = inject(Router);

  sucursales = [
    { id: 1, nombre: 'Santa Fe' },
    { id: 2, nombre: 'Paraná' },
    { id: 3, nombre: 'Santo Tomé' },
    { id: 4, nombre: 'Rafaela' },
    { id: 5, nombre: 'Rosario' },
  ];

  todayISO = new Date().toISOString().slice(0,10);
  tomorrowISO = new Date(Date.now() + 86400000).toISOString().slice(0,10);

  form = this.fb.group({
    pickupBranchId: [1, Validators.required],
    returnBranchId: [1, Validators.required],
    fromISO: [this.todayISO, Validators.required],
    toISO: [this.tomorrowISO, Validators.required],
    driverAge: [25, [Validators.required, Validators.min(18)]],
  });

  buscar() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.router.navigate(['/cotizar/resultados'], { queryParams: this.form.value });
  }
}
