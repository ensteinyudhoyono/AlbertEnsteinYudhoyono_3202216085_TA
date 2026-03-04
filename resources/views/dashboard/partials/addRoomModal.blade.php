<div class="modal fade" id="addRoom" tabindex="-1" aria-labelledby="formModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel">Form Tambah {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="text-align: left;">
                <form action="/dashboard/rooms" method="post" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="room_id" id="room_id">
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Ruangan</label>
                        <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" required value="{{ old('code') }}">
                        @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Ruangan</label>
                        <input type="text" class="form-control  @error('name') is-invalid @enderror" id="name" name="name" required value="{{ old('name') }}">
                        @error('name')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class='mb-3'>
                        <label for='img' class='form-label'>Foto Ruangan</label>
                        <input class="form-control @error('img') is-invalid @enderror" type='file' id='img_add' name='img' accept="image/*"/>
                        <div class="form-text">Format yang didukung: JPG, PNG, GIF. Ukuran maksimal: 2MB</div>
                        
                        <!-- Image Preview for Add Modal -->
                        <div id="addImagePreview" class="mt-2" style="display: none;">
                            <label class="form-label small text-muted">Preview Gambar:</label>
                            <div>
                                <img id="addImage" src="" alt="Room Image Preview" class="img-thumbnail" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                            </div>
                        </div>
                        
                        @error('img')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="mb-3 row">
                        <div class="col-6">
                            <label for="floor" class="form-label">Lantai</label>
                            <input type="number" class="form-control @error('floor') is-invalid @enderror" id="floor" name="floor" required value="{{ old('floor') }}">
                        </div>
                        <div class="col-6">
                            <label for="capacity" class="form-label">Kapasitas</label>
                        <input type="number"  class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" required value="{{ old('capacity') }}">
                        </div>
                    </div>


                    <div class="mb-3">
                        <label for="description" class="form-label  @error('description') is-invalid @enderror">deskripsi ruangan</label>
                        <textarea name="description" id="description" cols="30" rows="5" class="form-control" required>{{ old('description') }}</textarea>
                        @error('description')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>