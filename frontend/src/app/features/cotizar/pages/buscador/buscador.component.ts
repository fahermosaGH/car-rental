import { Component, inject, OnInit, AfterViewInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { RouterModule } from '@angular/router';
import { LocationService, Location } from '../../services/location.service';
import { MapaSucursalesComponent } from '../../components/mapa-sucursales/mapa-sucursales.component';

@Component({
  selector: 'app-buscador',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    MapaSucursalesComponent,
    RouterModule
  ],
  templateUrl: './buscador.component.html',
  styleUrls: ['./buscador.component.css'],
})
export class BuscadorComponent implements OnInit, AfterViewInit {
  private fb = inject(FormBuilder);
  private router = inject(Router);
  private locationService = inject(LocationService);

  sucursales: Location[] = [];
  cargando = true;

  todayISO = new Date().toISOString().slice(0, 10);
  tomorrowISO = new Date(Date.now() + 86400000).toISOString().slice(0, 10);

  // 🔹 mínimo permitido para la devolución (se actualiza cuando cambia el retiro)
  minEndDate = this.tomorrowISO;

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

  tab: string = 'populares';

  destinosPopulares = [
    'El Calafate',
    'San Juan',
    'Neuquén',
    'Río Gallegos',
    'Ushuaia',
    'Bahía Blanca'
  ];

  aeropuertos = [
    'Aeropuerto de Ezeiza (EZE)',
    'Aeropuerto de Córdoba (COR)',
    'Aeropuerto de Mendoza (MDZ)',
    'Aeropuerto de Cancún (CUN)',
    'Aeropuerto de Miami (MIA)'
  ];

  categoriasAutos = [
    'Económicos',
    'Compactos',
    'Intermedios',
    'SUV',
    'Pickups',
    'Premium'
  ];

  promociones = [
    '10% OFF pagando online',
    'Promo fin de semana',
    'Upgrade gratis sujeto a disponibilidad'
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

  ngAfterViewInit() {
    const items = document.querySelectorAll('.faq-item');

    items.forEach(item => {
      const btn = item.querySelector('.faq-question');
      btn?.addEventListener('click', () => {
        item.classList.toggle('open');
      });
    });
  }

  // 🔹 Cuando cambia la fecha de retiro
  onStartDateChange(): void {
    const start = this.form.value.startAt || this.todayISO;

    // Si por alguna razón el usuario pone algo anterior a hoy (manualmente), lo corregimos
    if (start < this.todayISO) {
      this.form.patchValue({ startAt: this.todayISO });
    }

    // Recalcular mínimo de devolución: al menos 1 día después del retiro
    const startDate = new Date(this.form.value.startAt || this.todayISO);
    const nextDay = new Date(startDate.getTime() + 24 * 60 * 60 * 1000);
    const nextDayISO = nextDay.toISOString().slice(0, 10);

    this.minEndDate = nextDayISO;

    const end = this.form.value.endAt || '';
    if (!end || end <= this.form.value.startAt!) {
      this.form.patchValue({ endAt: nextDayISO });
    }
  }

  buscar() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      alert('⚠️ Completá todos los campos antes de buscar.');
      return;
    }

    const values = this.form.value;

    // 🔒 Validación extra por si escribe a mano fechas raras
    if (values.startAt! < this.todayISO) {
      alert('⚠️ La fecha de retiro no puede ser anterior a hoy.');
      return;
    }
    if (values.endAt! <= values.startAt!) {
      alert('⚠️ La fecha de devolución debe ser posterior a la de retiro.');
      return;
    }

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

