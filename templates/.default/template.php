<div id="vue-payment-qr-code">
    <div v-if="!isMobile && this.qr" class="qr-container">
        <img class="qr-image" :src="`data:image/png;base64,${this.qr}`">
        <div class="qr-container-title">
            <h1>Для оплаты</h1>
            <div>отсканируйте QR-код в мобильном приложении банка или штатной камерой телефона</div>
        </div>
    </div>
    <div v-else-if="isMobile">
        <div class="list">
            <p class="list__title">Выберите банковское приложение и подтвердите оплату</p>
            <div class="list__search">
                <input type="text" v-model="searchTerm" @input="filterBanks" placeholder="Поиск по банкам">
            </div>
            <div class="list__inner-container">
                <p class="list__title">Все банки</p>
                <div class="list__inner" v-if="issetFilteredBanks">
                    <div v-for="(item, index) in filteredBanks" :key="index" :title="item.bankName">
                        <a class="list__list-item list-item" :href="getBankLink(item.schema, item.package_name)">
                            <img width="50px" height="50px" class="list-item__logo" :src="item.logoURL" :alt="item.bankName" />
                            <span class="list-item__name">{{ item.bankName }}</span>
                        </a>
                    </div>
                </div>
                <div v-else-if="!isRequest">По вашему запросу ничего не найдено</div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        qrSection = new Vue({
            el: "#vue-payment-qr-code",
            data: {
                qr: "<?= $arResult['QR'] ?>",
                url: "<?= $arResult['QR_URL'] ?>",
                check_payment: "<?= $arResult['CHECK_PAYMENT'] ?>",
                file: "<?= $arResult['FILE_CHECK_PAYMENT'] ?>",
                method: "<?= addslashes($arResult['METHOD_CHECK_PAYMENT']) ?>",
                redirect_url: "<?= $arResult['REDIRECT_URL'] ?>",
                issetOrderId: "<?= $arResult['ISSET_ORDER_ID'] ?>",
                banks: [],
                filteredBanks: [],
                isRequest: true,
                searchTerm: "",
                iOS: (!window.MSStream && /iPad|iPhone|iPod/.test(navigator.userAgent)) ||
                    /^((?!chrome|android).)*safari/i.test(navigator.userAgent),
                isMobile: (/Android|webOS|iPhone|iPad|iPod|BlackBerry|BB|PlayBook|IEMobile|Windows Phone|Kindle|Silk|Opera Mini/i
                    .test(navigator.userAgent)),
            },
            async created() {
                if (this.isMobile) {
                    this.getBanks();
                }
                if (this.check_payment) {
                    setTimeout(() => {
                        setInterval(async () => {
                            await this.checkPayment()
                        }, 5000)
                    }, 15000)
                }
            },
            methods: {
                async getBanks() {
                    try {
                        const response = await fetch("https://qr.nspk.ru/proxyapp/c2bmembers.json");

                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }

                        const data = await response.json();
                        this.banks = data.dictionary;
                        this.filteredBanks = data.dictionary
                    } catch (error) {
                        console.error('Error:', error);
                    } finally {
                        this.isRequest = false
                    }
                },
                async checkPayment() {
                    try {
                        let requestData = `url='${this.url}'&file='${this.file}'&method='${this.method}'`;
                        const response = await fetch('/bitrix/services/main/ajax.php?mode=class&c=Itb:qrShow&action=checkPayment', {
                            method: "POST",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                            },
                            body: 'requestData=' + encodeURIComponent(requestData),
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }

                        const data = await response.json();
                        if (data.data.paid === true) {
                            let redirectUrl;
                            if (this.redirect_url.length > 0) {
                                if (this.issetOrderId) {
                                    redirectUrl = `${this.redirect_url}${data.data.order_id}`
                                } else {
                                    redirectUrl = this.redirect_url
                                }
                                window.location.href = redirectUrl;
                            }
                        }
                    } catch (error) {
                        console.error('Error:', error);
                    }
                },
                getBankLink(schema, package_name) {
                    url = this.iOS ?
                        `${this.url.replace("https:", schema + ":")}` :
                        `${this.url.replace(
                          "https:",
                          "intent:"
                        )}#Intent;scheme=${schema};package=${package_name};end;`;
                    return url
                },
                filterBanks() {
                    const normalizedSearchTerm = this.searchTerm.toLowerCase().trim();

                    if (!normalizedSearchTerm) {
                        this.filteredBanks = this.banks;
                        return;
                    }

                    this.filteredBanks = this.banks.filter(bank =>
                        bank.bankName.toLowerCase().includes(normalizedSearchTerm)
                    );
                },
            },
            computed: {
                issetFilteredBanks() {
                    return !this.isRequest && this.filteredBanks.length > 0
                }
            }
        });
    });
</script>