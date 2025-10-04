// public/js/reservation-check.js
(function () {
    function init() {
        const form = document.querySelector('form.ea-new-form, form.ea-edit-form');
        if (!form) {
            console.warn('[reservation-check] Esperando formulario...');
            setTimeout(init, 400);
            return;
        }

        // 🧩 Seleccionamos correctamente los campos
        const vehicleInput = form.querySelector('input[name="Reservation[vehicle]"]');
        const startInput = form.querySelector('input[name="Reservation[startAt]"]');
        const endInput = form.querySelector('input[name="Reservation[endAt]"]');
        const submitBtn = form.querySelector('button[type="submit"]');

        if (!vehicleInput || !startInput || !endInput || !submitBtn) {
            console.warn('[reservation-check] Campos no encontrados todavía...');
            setTimeout(init, 400);
            return;
        }

        // Evitar inicializar dos veces
        if (form.dataset.reservationCheckReady) return;
        form.dataset.reservationCheckReady = 'true';
        console.log('✅ reservation-check activo.');

        // Caja visual informativa
        const info = document.createElement('div');
        info.id = 'availability-msg';
        info.style.marginTop = '12px';
        info.style.padding = '10px';
        info.style.borderRadius = '6px';
        info.style.fontWeight = '600';
        info.style.display = 'none';
        submitBtn.insertAdjacentElement('afterend', info);

        const show = (msg, color) => {
            info.textContent = msg;
            info.style.display = 'block';
            info.style.background = color;
            info.style.color = '#fff';
        };

        async function check() {
            const vehicleId = vehicleInput.value;
            const startAt = startInput.value;
            const endAt = endInput.value;

            if (!vehicleId || !startAt || !endAt) {
                info.style.display = 'none';
                submitBtn.disabled = false;
                return;
            }

            show('⏳ Comprobando disponibilidad...', '#263238');
            submitBtn.disabled = true;

            try {
                const res = await fetch(
                    `/api/check-availability?vehicleId=${encodeURIComponent(vehicleId)}&startAt=${encodeURIComponent(startAt)}&endAt=${encodeURIComponent(endAt)}`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const data = await res.json();

                if (data.available) {
                    show('✅ Vehículo disponible en las fechas seleccionadas.', '#1b5e20');
                    submitBtn.disabled = false;
                } else {
                    show('🚫 Vehículo NO disponible en ese rango.', '#b71c1c');
                    submitBtn.disabled = true;
                }
            } catch (e) {
                console.error('[reservation-check] Error:', e);
                show('⚠️ Error al comprobar disponibilidad.', '#b71c1c');
                submitBtn.disabled = true;
            }
        }

        [vehicleInput, startInput, endInput].forEach(el => {
            el.addEventListener('change', check);
            el.addEventListener('blur', check);
        });
    }

    document.addEventListener('ea.form.render', init);
    document.addEventListener('turbo:load', init);
    document.addEventListener('DOMContentLoaded', init);
    setTimeout(init, 800);
})();
