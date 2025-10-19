import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { CotizarService } from '../../services/cotizar.service';

@Component({
  selector: 'app-buscador',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './buscador.component.html',
  styleUrls: ['./buscador.component.css']
})
export class BuscadorComponent implements OnInit {
  private fb = inject(FormBuilder);
  private router = inject(Router);
  private cotizarService = inject(CotizarService);

  sucursales: any[] = [];

  todayISO = new Date().toISOString().slice(0, 10);
  tomorrowISO = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

  form = this.fb.group({
    pickupLocationId: [1, Validators.required],
    dropoffLocationId: [1, Validators.required],
    startAt: [this.todayISO, Validators.required],
    endAt: [this.tomorrowISO, Validators.required],
    driverAge: [25, [Validators.required, Validators.min(18)]],
  });

  ngOnInit() {
    this.cargarSucursales();
  }

  // 🏙️ Cargar sucursales desde el backend
  cargarSucursales() {
    this.cotizarService.obtenerSucursales().subscribe({
      next: (data) => {
        console.log('📍 Sucursales recibidas:', data);
        this.sucursales = data;
      },
      error: (err) => {
        console.error('❌ Error al cargar sucursales:', err);
        alert('Error al cargar las sucursales.');
      }
    });
  }

  buscar() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      alert('⚠️ Completá todos los campos antes de buscar.');
      return;
    }

    const values = this.form.value;
    console.log('🔍 Enviando búsqueda:', values);

    this.router.navigate(['/cotizar/resultados'], {
      queryParams: {
        pickupLocationId: values.pickupLocationId,
        dropoffLocationId: values.dropoffLocationId,
        startAt: values.startAt,
        endAt: values.endAt,
        driverAge: values.driverAge,
      },
    });
  }
}
