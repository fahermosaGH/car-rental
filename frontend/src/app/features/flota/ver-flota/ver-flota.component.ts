import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { CotizarService } from '../../cotizar/services/cotizar.service';

@Component({
  selector: 'app-ver-flota',
  standalone: true,
  imports: [CommonModule, HttpClientModule, FormsModule],
  templateUrl: './ver-flota.component.html',
  styleUrls: ['./ver-flota.component.css'],
})
export class VerFlotaComponent implements OnInit {

  private cotizarService = inject(CotizarService);

  loading = false;
  loadingFilter = false;
  error: string | null = null;

  vehicles: any[] = [];
  filtered: any[] = [];

  categories = [
    'Todos', 'Económico', 'Compacto', 'SUV',
    'Camioneta', 'Sedán', 'Premium', 'Largos'
  ];

  selectedCategory = 'Todos';
  sort: 'az' | 'za' = 'az';

  searchText: string = '';
  searchTrigger: string = '';

  ngOnInit(): void {
    this.loadVehicles();
  }

  loadVehicles(): void {
    this.loading = true;
    this.error = null;

    this.cotizarService.buscarVehiculos().subscribe({
      next: (data) => {
        this.vehicles = data;
        this.applyFilters(false);
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudo cargar la flota de vehículos.';
        this.loading = false;
      }
    });
  }

  onSearchEnter() {
    this.searchTrigger = this.searchText.trim().toLowerCase();
    this.applyFilters(true);
  }

  selectCategory(cat: string) {
    this.selectedCategory = cat;
    this.applyFilters(true);
  }

  changeSort(dir: 'az' | 'za') {
    this.sort = dir;
    this.applyFilters(true);
  }

  applyFilters(animated: boolean = true) {
    if (animated) {
      this.loadingFilter = true;
      setTimeout(() => {
        this.runFiltering();
        this.loadingFilter = false;
      }, 350);
    } else {
      this.runFiltering();
    }
  }

  private runFiltering() {
    let items = [...this.vehicles];

    if (this.selectedCategory !== 'Todos') {
      items = items.filter(v => (v.category || '').toLowerCase() === this.selectedCategory.toLowerCase());
    }

    if (this.searchTrigger !== '') {
      items = items.filter(v =>
        `${v.brand} ${v.model}`.toLowerCase().includes(this.searchTrigger)
      );
    }

    if (this.sort === 'az') {
      items.sort((a, b) => (a.brand + a.model).localeCompare(b.brand + b.model));
    } else {
      items.sort((a, b) => (b.brand + b.model).localeCompare(a.brand + a.model));
    }

    this.filtered = items;
  }
}
