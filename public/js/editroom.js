$(document).ready(function () {
    // Handle edit room button click
    $(document).on("click", ".editroom", function (e) {
        // Prevent navigating to href (especially in rooms index actions)
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        const id = $(this).data("id");
        const code = $(this).data("code");
        
        // Reset form and image previews
        resetEditModal();
        
        $.ajax({
            url: "/dashboard/rooms/" + code + "/edit",
            data: {
                id: id,
                code: code,
            },
            type: "get",
            dataType: "json",
            success: function (data) {
                console.log(data);
                $("#id").val(data.id);
                $("#code").val(data.code);
                $("#name").val(data.name);
                $("#floor").val(data.floor);
                $("#capacity").val(data.capacity);
                $("#description").val(data.description);
                $("#editform").attr("action", "/dashboard/rooms/" + data.code);
                
                // Show current image if exists
                if (data.img && data.img !== 'room-image/roomdefault.jpg') {
                    const imageUrl = '/storage/' + data.img + '?v=' + (data.updated_at ? new Date(data.updated_at).getTime() : Date.now());
                    $("#currentImage").attr('src', imageUrl);
                    $("#currentImagePreview").show();
                } else if (data.img === 'room-image/roomdefault.jpg') {
                    const defaultImageUrl = '/img/roomdefault.jpg?v=' + Date.now();
                    $("#currentImage").attr('src', defaultImageUrl);
                    $("#currentImagePreview").show();
                } else {
                    $("#currentImagePreview").hide();
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading room data:', error);
                alert('Terjadi kesalahan saat memuat data ruangan. Silakan coba lagi.');
            }
        });
    });

    // Guard: prevent submitting if action not set correctly yet
    $(document).on('submit', '#editform', function(e){
        var action = $(this).attr('action') || '';
        if (!action.match(/\/dashboard\/rooms\/.+/)) {
            e.preventDefault();
            alert('Data ruangan belum selesai dimuat. Silakan tunggu sebentar lalu coba lagi.');
            return false;
        }
    });
    
    // Handle file input change for new image preview
    $("#img").on("change", function() {
        const file = this.files[0];
        if (file) {
            // Validate file type
            if (!file.type.match('image.*')) {
                alert('Harap pilih file gambar yang valid (JPG, PNG, GIF)');
                this.value = '';
                $("#newImagePreview").hide();
                return;
            }
            
            // Validate file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file terlalu besar. Maksimal 2MB.');
                this.value = '';
                $("#newImagePreview").hide();
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $("#newImage").attr('src', e.target.result);
                $("#newImagePreview").show();
            };
            reader.readAsDataURL(file);
        } else {
            $("#newImagePreview").hide();
        }
    });
    
    // Reset modal when closed
    $('#editRoom').on('hidden.bs.modal', function () {
        resetEditModal();
    });
    
    // Handle file input change for add room image preview
    $("#img_add").on("change", function() {
        const file = this.files[0];
        if (file) {
            // Validate file type
            if (!file.type.match('image.*')) {
                alert('Harap pilih file gambar yang valid (JPG, PNG, GIF)');
                this.value = '';
                $("#addImagePreview").hide();
                return;
            }
            
            // Validate file size (2MB max)
            if (file.size > 2 * 1024 * 1024) {
                alert('Ukuran file terlalu besar. Maksimal 2MB.');
                this.value = '';
                $("#addImagePreview").hide();
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $("#addImage").attr('src', e.target.result);
                $("#addImagePreview").show();
            };
            reader.readAsDataURL(file);
        } else {
            $("#addImagePreview").hide();
        }
    });
    
    // Reset add modal when closed
    $('#addRoom').on('hidden.bs.modal', function () {
        resetAddModal();
    });
    
    function resetEditModal() {
        // Clear form inputs
        $("#img").val('');
        
        // Hide previews
        $("#currentImagePreview").hide();
        $("#newImagePreview").hide();
        
        // Clear image sources
        $("#currentImage").attr('src', '');
        $("#newImage").attr('src', '');
    }
    
    function resetAddModal() {
        // Clear form inputs
        $("#img_add").val('');
        
        // Hide preview
        $("#addImagePreview").hide();
        
        // Clear image source
        $("#addImage").attr('src', '');
    }
});
