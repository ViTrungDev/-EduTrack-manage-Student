const showBtn = document.querySelector(".show-status-click");
const statusIndicator = document.getElementById("statusIndicator");

showBtn.addEventListener("click", () => {
  statusIndicator.style.display === "none"
    ? (statusIndicator.style.display = "block")
    : (statusIndicator.style.display = "none");
});
console.log("JavaScript loaded successfully");
