import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { LocationService, Location } from '../../services/location.service';
import { MapaSucursalesComponent } from '../../components/mapa-sucursales/mapa-sucursales.component';

@Component({
  selector: 'app-buscador',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, MapaSucursalesComponent],
  templateUrl: './buscador.component.html',
  styleUrls: ['./buscador.component.css'],
})
export class BuscadorComponent implements OnInit {
  private fb = inject(FormBuilder);
  private router = inject(Router);
  private locationService = inject(LocationService);

  sucursales: Location[] = [];
  cargando = true;

  todayISO = new Date().toISOString().slice(0, 10);
  tomorrowISO = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

  form = this.fb.group({
    pickupLocationId: [1, Validators.required],
    dropoffLocationId: [1, Validators.required],
    startAt: [this.todayISO, Validators.required],
    endAt: [this.tomorrowISO, Validators.required],
    driverAge: [25, [Validators.required, Validators.min(18)]],
  });

  ciudades = [
  { nombre: 'Buenos Aires', region: 'CABA', img: '/assets/imagenes/ba.jpg' },
  { nombre: 'Mendoza', region: 'MZ', img: '/assets/imagenes/mendoza.jpg' },
  { nombre: 'Salta', region: 'SA', img: '/assets/imagenes/salta.jpg' },
  { nombre: 'Córdoba', region: 'CB', img: '/assets/imagenes/cordoba.jpg' },
];

  ngOnInit() {
    this.locationService.obtenerSucursales().subscribe({
      next: (data) => {
        this.sucursales = data;
        this.cargando = false;
      },
      error: (err) => {
        console.error('Error cargando sucursales', err);
        this.cargando = false;
      },
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
