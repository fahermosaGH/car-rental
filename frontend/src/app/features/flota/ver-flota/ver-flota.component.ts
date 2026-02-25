import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { CotizarService } from '../../cotizar/services/cotizar.service';
import { VehicleOption } from '../../cotizar/models/quote';

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

  vehicles: VehicleOption[] = [];
  filtered: VehicleOption[] = [];

  categories: string[] = ['Todos'];
  selectedCategory = 'Todos';

  sort: 'az' | 'za' = 'az';

  searchText = '';
  searchTrigger = '';

  ngOnInit(): void {
    this.loadVehicles();
  }

  loadVehicles(): void {
    this.loading = true;
    this.error = null;

    this.cotizarService.buscarVehiculos().subscribe({
      next: (data) => {
        this.vehicles = Array.isArray(data) ? data : [];
        this.buildCategories();
        this.applyFilters(false);
        this.loading = false;
      },
      error: () => {
        this.error = 'No se pudo cargar la flota de vehículos.';
        this.loading = false;
      },
    });
  }

  private normalize(s: string): string {
    return (s || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();
  }

  private buildCategories(): void {
    const set = new Set<string>();

    for (const v of this.vehicles) {
      const cat = (v.category || '').toString().trim();
      if (cat) set.add(cat);
    }

    const list = Array.from(set).sort((a, b) => a.localeCompare(b));
    this.categories = ['Todos', ...list];

    if (!this.categories.includes(this.selectedCategory)) {
      this.selectedCategory = 'Todos';
    }
  }

  onSearchEnter(): void {
    this.searchTrigger = this.searchText.trim().toLowerCase();
    this.applyFilters(true);
  }

  selectCategory(cat: string): void {
    this.selectedCategory = cat;
    this.applyFilters(true);
  }

  changeSort(dir: 'az' | 'za'): void {
    this.sort = dir;
    this.applyFilters(true);
  }

  applyFilters(animated = true): void {
    if (animated) {
      this.loadingFilter = true;
      setTimeout(() => {
        this.runFiltering();
        this.loadingFilter = false;
      }, 200);
    } else {
      this.runFiltering();
    }
  }

  private runFiltering(): void {
    let items = this.vehicles.slice();

    // filtro categoría
    if (this.selectedCategory !== 'Todos') {
      const selected = this.normalize(this.selectedCategory);
      items = items.filter((v) => this.normalize((v.category || '').toString()) === selected);
    }

    // búsqueda por marca/modelo
    if (this.searchTrigger !== '') {
      items = items.filter((v) => {
        const brand = (v.brand || '').toString();
        const model = (v.model || '').toString();
        return (brand + ' ' + model).toLowerCase().includes(this.searchTrigger);
      });
    }

    // orden
    items.sort((a, b) => {
      const aKey = ((a.brand || '') + ' ' + (a.model || '')).toString();
      const bKey = ((b.brand || '') + ' ' + (b.model || '')).toString();
      return this.sort === 'az' ? aKey.localeCompare(bKey) : bKey.localeCompare(aKey);
    });

    this.filtered = items;
  }
}