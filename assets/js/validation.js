// Form Validation Functions
function validateBlogForm() {
    const title = document.getElementById('title');
    const category = document.getElementById('category_id');
    const content = document.getElementById('content');

    if (title && !title.value.trim()) {
        alert('Blog Title is required.');
        title.focus();
        return false;
    }
    if (category && (!category.value || category.value === "")) {
        alert('Please select a Category.');
        category.focus();
        return false;
    }
    if (content && !content.value.trim()) {
        alert('Blog Content cannot be empty.');
        content.focus();
        return false;
    }

    // Check file extensions if selected
    const imageInput = document.getElementById('featured_image');
    if (imageInput && imageInput.files.length > 0) {
        const fileName = imageInput.files[0].name;
        const ext = fileName.split('.').pop().toLowerCase();
        const allowedImages = ['jpg', 'jpeg', 'png', 'webp'];
        if (!allowedImages.includes(ext)) {
            alert('Invalid Image extension! Allowed: jpg, jpeg, png, webp');
            return false;
        }
    }

    const docInput = document.getElementById('downloadable_file');
    if (docInput && docInput.files.length > 0) {
        const fileName = docInput.files[0].name;
        const ext = fileName.split('.').pop().toLowerCase();
        const allowedDocs = ['pdf', 'doc', 'docx', 'txt'];
        if (!allowedDocs.includes(ext)) {
            alert('Invalid Document extension! Allowed: pdf, doc, docx, txt');
            return false;
        }
    }

    return true;
}

function validateRegisterForm() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');

    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            alert('Passwords do not match!');
            confirmPassword.focus();
            return false;
        }
        if (password.value.length < 6) {
            alert('Password must be at least 6 characters long.');
            password.focus();
            return false;
        }
    }
    return true;
}
