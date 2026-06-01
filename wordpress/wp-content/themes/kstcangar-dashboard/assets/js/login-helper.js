async function autoLogin() {
  try {
    const existingToken = localStorage.getItem("kst_access_token");
    if (existingToken) {
      return;
    }

    const response = await apiRequest("/auth/login", "POST", {
      username: "kstcangar_wp",
      password: "Sin3020mm25mt44!",
    });

    const token = response?.accessToken || response?.data?.token;
    if (token) {
      localStorage.setItem("kst_access_token", token);
      location.reload();
    } else {
      console.error(
        "Auto login gagal: token tidak ditemukan di response",
        response,
      );
    }
  } catch (err) {
    console.error("Auto login gagal:", err);
  }
}

document.addEventListener("DOMContentLoaded", autoLogin);
