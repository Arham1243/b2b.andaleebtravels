function showImage(input, previewImgId, filenamePreviewId) {
    var file = input.files && input.files[0];
    var allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
    ];

    if (!file) {
        return;
    }

    if (!allowedTypes.includes(file.type)) {
        alert("Please select a valid image file (JPG, PNG, or GIF).");
        input.value = "";
        return;
    }

    var reader = new FileReader();
    reader.onload = function (e) {
        var preview = document.getElementById(previewImgId);
        if (preview) {
            preview.src = e.target.result;
            preview.style.display = "";
        }
        var nameEl = document.getElementById(filenamePreviewId);
        if (nameEl) {
            nameEl.textContent = file.name;
        }
    };
    reader.readAsDataURL(file);
}
