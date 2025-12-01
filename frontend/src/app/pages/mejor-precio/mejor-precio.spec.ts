import { ComponentFixture, TestBed } from '@angular/core/testing';
import { MejorPrecioComponent } from './mejor-precio';

describe('MejorPrecioComponent', () => {
  let component: MejorPrecioComponent;
  let fixture: ComponentFixture<MejorPrecioComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [MejorPrecioComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(MejorPrecioComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
