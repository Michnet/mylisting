window.onload = () => {
const passwordField = document.querySelectorAll('input[name="password"]');
const togglePassword = document.querySelectorAll(".password-toggle-icon i");

togglePassword.forEach((el) => {
    el.addEventListener("click", function () {
        
        passwordField.forEach((ps) => {
            if (ps.type === "password") {
                ps.type = "text";
                el.classList.remove("fa-eye");
                el.classList.add("fa-eye-slash");
                } else {
                ps.type = "password";
                el.classList.remove("fa-eye-slash");
                el.classList.add("fa-eye");
                }
        })
    });
})


}