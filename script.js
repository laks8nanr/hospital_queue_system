//navigation logic

// Store department selection
function selectDept(dept) {
  localStorage.setItem("department", dept);
  window.location.href = "doctors.html";
}

// Store doctor & slot
function selectDoctor(doctor, slot) {
  localStorage.setItem("doctor", doctor);
  localStorage.setItem("slot", slot);
  window.location.href = "patient_form.html";
}

// Fill hidden inputs on form page
document.addEventListener("DOMContentLoaded", () => {
  if (document.getElementById("dept")) {
    document.getElementById("dept").value = localStorage.getItem("department");
    document.getElementById("doctor").value = localStorage.getItem("doctor");
    document.getElementById("slot").value = localStorage.getItem("slot");
  }
});
