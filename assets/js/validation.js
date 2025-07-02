function validateSignup() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;

    if (password.length < 6) {
        alert("Password must be at least 6 characters");
        return false;
    }
    if (password !== confirm) {
        alert("Passwords do not match");
        return false;
    }
    return true;
}

function validateLogin() {
    // You could add further checks like blocking known bad domains
    return true; // Let the server handle further verification
}
