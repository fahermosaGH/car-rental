import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MejorPrecio } from './mejor-precio';

describe('MejorPrecio', () => {
  let component: MejorPrecio;
  let fixture: ComponentFixture<MejorPrecio>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [MejorPrecio]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MejorPrecio);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
