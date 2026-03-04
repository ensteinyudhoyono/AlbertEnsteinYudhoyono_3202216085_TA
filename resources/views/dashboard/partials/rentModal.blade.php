<div class="modal fade" id="pinjamRuangan" tabindex="-1" aria-labelledby="formModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel">Form {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="text-align: left;">
                <!-- Warning Section -->
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle-fill"></i>
                    <strong>Informasi Peminjaman:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Peminjaman akan dicek ketersediaan ruangan secara otomatis</li>
                                                <li>Ruangan yang sudah <strong>disetujui</strong> tidak dapat dipinjam pada waktu yang sama</li>
                        <li>Peminjaman akan menunggu persetujuan admin</li>
                        <li>Pastikan waktu selesai setelah waktu mulai</li>
                    </ul>
                </div>
                
                <form action="/dashboard/rents" method="post">
                    @csrf
                    <div class="mb-3">
                        <label for="room_id" class="form-label d-block">Kode Ruangan</label>
                        <select class="form-select @error('room_id') is-invalid @enderror" aria-label="Default select example" name="room_id"
                            id="room_id" required>
                            @if (count(request()->segments()) < 3)
                                <option selected disabled>Pilih Kode Ruangan</option>
                            @endif
                            @foreach ($rooms as $room)
                                @if ($room->code == request()->segment(count(request()->segments())))
                                    <option value="{{ $room->id }}" selected>
                                        {{ $room->code }} - {{ $room->name }}
                                        @if($room->current_rental)
                                            (Sedang dipinjam)
                                        @endif
                                    </option>
                                @else
                                    <option value="{{ $room->id }}">
                                        {{ $room->code }} - {{ $room->name }}
                                        @if($room->current_rental)
                                            (Sedang dipinjam)
                                        @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('room_id')
                        <div class="invalid-feedback d-block">
                            <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="time_start" class="form-label">Mulai Pinjam</label>
                        <input type="datetime-local" class="form-control" id="time_start_use" 
                        name="time_start_use"  
                        value="{{ old('time_start_use')}}"
                        required>
                    </div>
                    <div class="mb-3">
                        <label for="time_end" class="form-label">Selesai Pinjam</label>
                        <input type="datetime-local" class="form-control" id="time_end_use" 
                        name="time_end_use" 
                        value="{{ old('time_end_use')}}"
                        required>
                    </div>
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Tujuan</label>
                        <input type="text" class="form-control  @error('capacity') is-invalid @enderror" id="purpose" 
                        name="purpose" value="{{ old('purpose')}}" required>
                        @error('purpose')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pilih Item (Opsional)</label>
                        <div id="items-container">
                            @if(isset($items) && $items->count() > 0)
                                @foreach ($items as $item)
                                <div class="form-check mb-2">
                                    <input class="form-check-input item-checkbox" type="checkbox" 
                                           name="items[]" value="{{ $item->id }}" 
                                           id="item_{{ $item->id }}" data-max="{{ $item->quantity }}">
                                    <label class="form-check-label" for="item_{{ $item->id }}">
                                        {{ $item->name }} (Tersedia: {{ $item->quantity }})
                                    </label>
                                    <div class="item-quantity" style="display: none; margin-left: 20px;">
                                        <label for="quantity_{{ $item->id }}" class="form-label">Jumlah:</label>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="item_quantities[{{ $item->id }}]" 
                                               id="quantity_{{ $item->id }}" 
                                               min="1" max="{{ $item->quantity }}" 
                                               style="width: 100px; display: inline-block;">
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <p class="text-muted">Tidak ada item tersedia saat ini.</p>
                            @endif
                        </div>
                        @error('items')
                        <div class="invalid-feedback d-block">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    const timeStartInput = document.getElementById('time_start_use');
    const timeEndInput = document.getElementById('time_end_use');
    const roomSelect = document.getElementById('room_id');
    
    // Item checkbox functionality
    itemCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const itemId = this.value;
            const quantityDiv = document.querySelector(`#quantity_${itemId}`).parentElement;
            
            if (this.checked) {
                quantityDiv.style.display = 'block';
                document.querySelector(`#quantity_${itemId}`).value = 1;
            } else {
                quantityDiv.style.display = 'none';
                document.querySelector(`#quantity_${itemId}`).value = '';
            }
        });
    });
    
    // Time validation
    function validateTimeRange() {
        const startTime = new Date(timeStartInput.value);
        const endTime = new Date(timeEndInput.value);
        
        // Remove existing warning
        const existingWarning = document.getElementById('time-warning');
        if (existingWarning) {
            existingWarning.remove();
        }
        
        if (timeStartInput.value && timeEndInput.value) {
            if (endTime <= startTime) {
                const warning = document.createElement('div');
                warning.id = 'time-warning';
                warning.className = 'alert alert-warning mt-2';
                warning.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> <strong>Peringatan:</strong> Waktu selesai harus setelah waktu mulai!';
                
                timeEndInput.parentNode.appendChild(warning);
                return false;
            }
            
            // Check if end time is in the past
            if (endTime < new Date()) {
                const warning = document.createElement('div');
                warning.id = 'time-warning';
                warning.className = 'alert alert-warning mt-2';
                warning.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> <strong>Peringatan:</strong> Waktu selesai tidak boleh di masa lalu!';
                
                timeEndInput.parentNode.appendChild(warning);
                return false;
            }
        }
        
        return true;
    }
    
    // Add event listeners for time validation
    if (timeStartInput && timeEndInput) {
        timeStartInput.addEventListener('change', validateTimeRange);
        timeEndInput.addEventListener('change', validateTimeRange);
    }
    
    // Form submission validation - target specific form in rent modal
    const rentForm = document.querySelector('#pinjamRuangan form');
    if (rentForm) {
        rentForm.addEventListener('submit', function(e) {
            if (!validateTimeRange()) {
                e.preventDefault();
                alert('Mohon perbaiki waktu peminjaman sebelum melanjutkan!');
                return false;
            }
            
            // Show confirmation dialog
            if (!confirm('Apakah Anda yakin ingin mengajukan peminjaman ruangan ini?')) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>