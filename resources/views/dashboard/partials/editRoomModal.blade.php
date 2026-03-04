<div class="modal fade" id="editRoom" tabindex="-1" aria-labelledby="formModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formModalLabel">Form Edit {{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="text-align: left;">
                <form action="" method="post" enctype="multipart/form-data" id="editform">
                    @method('put')
                    @csrf
                    <input type="hidden" name="id" id="id">
                    <div class="mb-3">
                        <label for="code" class="form-label">Kode Ruangan</label>
                        <input type="text" class="form-control  @error('code') is-invalid @enderror" id="code" name="code" required value="{{ old('code') }}">
                        @error('code')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Nama Ruangan</label>
                        <input type="text" class="form-control" id="name" name="name" required value="{{ old('name') }}">
                    </div>
                    <div class='mb-3'>
                        <label for='img' class='form-label'>Foto Ruangan</label>
                        
                        <!-- Current Image Preview -->
                        <div id="currentImagePreview" class="mb-2" style="display: none;">
                            <label class="form-label small text-muted">Foto Saat Ini:</label>
                            <div>
                                <img id="currentImage" src="" alt="Current Room Image" class="img-thumbnail" style="max-width: 200px; max-height: 150px; object-fit: cover;">
                            </div>
                        </div>
                        
                        <input class="form-control @error('img') is-invalid @enderror" type='file' id='img' name='img' accept="image/*"/>
                        <div class="form-text">Upload gambar baru untuk mengganti foto saat ini. Format yang didukung: JPG, PNG, GIF. Ukuran maksimal: 2MB</div>
                        
                        <!-- New Image Preview -->
                        <div id="newImagePreview" class="mt-2" style="display: none;">
                            <label class="form-label small text-muted">Preview Gambar Baru:</label>
                            <div>
                                <img id="newImage" src="" alt="New Room Image Preview" class="img-thumbnail" style="max-width: 200px; max-height: 150px; object-fit: cover;">
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
                            <input type="number" class="form-control" id="floor" name="floor" required value="{{ old('floor') }}">
                        </div>
                        <div class="col-6">
                            <label for="capacity" class="form-label">Kapasitas</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" required value="{{ old('capacity') }}">
                        </div>
                    </div>


                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi Ruangan</label>
                        <textarea name="description" id="description" cols="30" rows="5" class="form-control" required>{{ old('description') }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="editbtn" name="editbtn">Simpan</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>