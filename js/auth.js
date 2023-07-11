async function getWallet() {
    const accounts = await ethereum.request({ method: 'eth_requestAccounts' });
    if ((await checkNetwork()) === true) {
        console.log(accounts[0]);
        return accounts[0];
    }
    return false;
}

async function checkNetwork() {
    const chainId = window.ethereum.networkVersion;
    if (chainId == '56') {
        return true;
    } else {
        return false;
    }
}

async function authUser(wallet) {
    const web3 = new Web3(window.ethereum);
    const refId = 0;
    wallet = wallet.toLowerCase();

    await web3.eth.getTransactionCount(wallet);

    $.ajax({
        url: '/api/user/find',
        type: 'post',
        data: {
            account: wallet,
        },
        cache: false,
        async: false,
        success: function (result) {
            if (result == 'No') {
                $.ajax({
                    url: '/api/user/add',
                    type: 'post',
                    data: {
                        account: wallet,
                        ref_id: refId,
                    },
                    cache: false,
                    async: false,
                    success: function (data) {
                        console.log(data);
                        loginF(wallet);
                    },
                    error: function (err) {
                        console.log(err);
                    },
                });
            } else {
                console.log(result);
                loginF(wallet);
            }
        },
        error: function (err) {
            console.log(err);
        },
    });
}

async function loginF(wallet) {
    wallet = wallet.toLowerCase();
    const web3 = new Web3(window.ethereum);

    $.ajax({
        url: '/api/user/find',
        data: {
            account: wallet,
        },
        type: 'post',
        cache: false,
        async: false,
        success: function (result) {
            data = JSON.parse(result);
            if (typeof data.nonce !== 'undefined') {
                web3.eth.personal.sign(data.nonce, data.account, '').then((signature) => {
                    $.ajax({
                        url: '/api/user/signin',
                        type: 'post',
                        data: { account: data.account, signature: signature },
                        cache: false,
                        async: false,
                        success: function (data) {
                            console.log(data);
                            setTimeout(function () {
                                const urlParams = new URLSearchParams(window.location.search);
                                const backUrl = urlParams.get('back_url');
                                const params = Object.fromEntries(urlParams.entries());
                                if (backUrl) {
                                    const form = document.createElement('form');
                                    form.action = backUrl;
                                    form.method = 'POST';

                                    for (const [key, value] of Object.entries(params)) {
                                        if (key === document.querySelector('meta[name="csrf-param"]').content) continue;

                                        const field = document.createElement('input');
                                        field.setAttribute('name', key);
                                        field.setAttribute('value', value);
                                        form.appendChild(field);
                                    }
                                    document.body.append(form);
                                    form.submit();
                                } else {
                                    window.location.href = '/cabinet';
                                }
                            }, 0);
                        },
                        error: function (err) {
                            console.log(err);
                        },
                    });
                });
            } else {
                console.log(data);
            }
        },
        error: function (err) {
            console.log(err);
        },
    });
}

window.addEventListener('DOMContentLoaded', async () => {
    const registerBtns = document.querySelectorAll('.auth-action-element');
    for (const element of registerBtns) {
        element.addEventListener('click', async (event) => {
            event.preventDefault();
            if (typeof window.ethereum !== 'undefined') {
                const wallet = await getWallet();
                if (wallet === false) {
                    alert('Wrong wallet account. Or you need to use Binance Smart Chain.');
                    return false;
                } else {
                    await authUser(wallet);
                }
            } else {
                alert('Install MetaMask first');
            }
        });
    }
});
