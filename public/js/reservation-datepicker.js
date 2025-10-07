console.log("✅ reservation-datepicker.js activo");

document.addEventListener('DOMContentLoaded', function () {
    const vehicleSelect = document.querySelector('select[name$="[vehicle]"]');
    const pickupSelect = document.querySelector('select[name$="[pickupLocation]"]');
    const startInput = document.querySelector('input[name$="[startAt]"]');
    const endInput = document.querySelector('input[name$="[endAt]"]');

    if (!vehicleSelect || !pickupSelect || !startInput || !endInput) return;

    let bookedRanges = [];

    async function loadBookedDates() {
        const vehicleId = vehicleSelect.value;
        const pickupId = pickupSelect.value;
        if (!vehicleId || !pickupId) return;

        try {
            const res = await fetch(`/api/booked-dates?vehicleId=${vehicleId}&locationId=${pickupId}`);
            const data = await res.json();
            bookedRanges = data.booked.map(r => ({ from: r.start, to: r.end }));
            applyFlatpickr();
        } catch (e) {
            console.error('Error al obtener fechas:', e);
        }
    }

    function applyFlatpickr() {
        const today = new Date();
        const options = {
            minDate: today, // no permite fechas pasadas
            disable: bookedRanges, // bloquea fechas ocupadas
            dateFormat: "Y-m-d H:i",
            enableTime: true,
            time_24hr: true
        };
        flatpickr(startInput, options);
        flatpickr(endInput, options);
    }

    vehicleSelect.addEventListener('change', loadBookedDates);
    pickupSelect.addEventListener('change', loadBookedDates);

    loadBookedDates();
});
