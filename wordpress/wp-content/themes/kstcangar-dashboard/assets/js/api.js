async function apiRequest(endpoint, method = "GET", body = null) {
  const token = localStorage.getItem("kst_access_token");

  let actualMethod = method;
  let useMethodOverride = false;

  if (method === "PUT" || method === "DELETE") {
    actualMethod = "POST";
    useMethodOverride = true;
  }

  const options = {
    method: actualMethod,
    headers: {
      "Content-Type": "application/json",
    },
  };

  if (token) {
    options.headers["Authorization"] = "Bearer " + token;
  }

  if (kstConfig.nonce) {
    options.headers["X-WP-Nonce"] = kstConfig.nonce;
  }

  if (useMethodOverride) {
    options.headers["X-HTTP-Method-Override"] = method;
  }

  if (body !== null) {
    options.body = JSON.stringify(body);
  } else if (useMethodOverride) {
    options.body = JSON.stringify({});
  }

  const url = kstConfig.baseUrl + endpoint;
  const response = await fetch(url, options);

  if (response.status === 401) {
    localStorage.removeItem("kst_access_token");
    location.reload();
    return;
  }

  const contentType = response.headers.get("content-type") || "";
  if (!contentType.includes("application/json")) {
    throw new Error(
      "Server mengembalikan response non-JSON (status " + response.status + "). Periksa konfigurasi REST API."
    );
  }

  const result = await response.json();

  if (!response.ok) {
    const msg =
      result?.error?.message ||
      result?.response?.error?.message ||
      result?.message ||
      "API Error " + response.status;
    throw new Error(msg);
  }

  return result.response ?? result;
}
