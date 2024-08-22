function depositarDinheiro() {
    const valor = prompt("Digite o valor para depósito:");

    if (valor && !isNaN(valor) && valor > 0) {
        fetch("dashboard.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `amount=${valor}&deposit=1`,
        })
            .then(response => response.text())
            .then(data => {
                location.reload();
            });
    } else {
        alert("Valor inválido para depósito.");
    }
}

function extraerDinero() {
    const valor = prompt("Digite o valor para saque:");

    if (valor && !isNaN(valor) && valor > 0) {
        fetch("dashboard.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `amount=${valor}&withdraw=1`,
        })
            .then(response => response.text())
            .then(data => {
                location.reload();
            });
    } else {
        alert("Valor inválido para saque.");
    }
}

function transferirDinero() {
    const cpfDestinatario = prompt("Digite o CPF do destinatário:");
    const valor = prompt("Digite o valor para transferência PIX:");

    if (cpfDestinatario && valor && !isNaN(valor) && valor > 0) {
        fetch("dashboard.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: `pix_key=${cpfDestinatario}&amount=${valor}&pix=1`,
        })
            .then(response => response.text())
            .then(data => {
                location.reload();
            });
    } else {
        alert("Dados inválidos para transferência.");
    }
}

document.getElementById("current-year").innerHTML = new Date().getFullYear();