import $ from 'jquery';

$(document).ready(function () {
    var currentFocus = 0;

    $('#search').on('input', function () {
        generateURL();
        var $this = this;

        $.ajax({
            url: APPURL + "/ajax/autocomplete",
            data: {
                term: $this.value
            }
        }).done(function (result) {
            var a, b, i, val = $this.value;

            /*close any already open lists of autocompleted values*/
            closeAllLists();

            if (!val) {
                return false;
            }
            currentFocus = -1;

            if (result.length > 0) {
                result = result.slice(0, 10);
                /*create a DIV element that will contain the items (values):*/
                a = document.createElement("DIV");
                a.setAttribute("id", $this.id + "-autocomplete-list");
                a.setAttribute("class", "autocomplete-items");

                $this.parentNode.appendChild(a);

                for (i = 0; i < result.length; i++) {
                    let res = result[i];
                    /*create a DIV element for each matching element:*/
                    b = document.createElement("DIV");
                    b.innerHTML = "<b>" + res.nomArticle + "</b>";
                    b.innerHTML += "<br/><span class='small'><b>Texte : </b>" + (res.texte.length > 100 ? res.texte.substr(0, 100) + "..." : res.texte) + "</span>";
                    b.innerHTML += "<br/><span class='small'><b>Nombre de débats : </b>" + res.nbDebats + "</span>";
                    b.innerHTML += "<br/><span class='small'><b>Nombre de citations : </b>" + res.nbCitations + "</span>";
                    b.innerHTML += "<br/><span class='small'><b>Nombre de modifications : </b>" + res.nbModifications + "</span>";
                    b.innerHTML += "<br/><span class='small'><b>Commission : </b>" + res.nomCommission + "</span>";
                    /*execute a function when someone clicks on the item value (DIV element):*/
                    b.addEventListener("click", function (e) {
                        window.location.href = ABSURL + "/article/details/" + res.id;
                        /*close the list of autocompleted values,
                        (or any other open lists of autocompleted values:*/
                        closeAllLists();
                    });
                    a.appendChild(b);
                }
            } else {
                /*create a DIV element that will contain the items (values):*/
                a = document.createElement("DIV");
                a.setAttribute("id", $this.id + "-autocomplete-list");
                a.setAttribute("class", "autocomplete-items");
                b = document.createElement("DIV");
                b.innerHTML = "Aucun article trouvé";

                a.appendChild(b);
                $this.parentNode.appendChild(a);
            }
        }).fail(function (error) {
            console.log(error);
        });
    }).on('keydown', function (e) {
        generateURL();
        /*execute a function presses a key on the keyboard:*/
        var x = document.getElementById(this.id + "-autocomplete-list");
        if (x) x = x.getElementsByTagName("div");

        if (e.keyCode === 40) {
            /*If the arrow DOWN key is pressed,
            increase the currentFocus variable:*/
            if (currentFocus < x.length - 1) {
                currentFocus++;
                /*and and make the current item more visible:*/
                addActive(x, currentFocus);
            }
        } else if (e.keyCode === 38) { //up
            /*If the arrow UP key is pressed,
            decrease the currentFocus variable:*/
            if (currentFocus > 0) {
                currentFocus--;
                /*and and make the current item more visible:*/
                addActive(x, currentFocus);
            }
        } else if (e.keyCode === 13) {
            /*If the ENTER key is pressed, prevent the form from being submitted,*/
            e.preventDefault();
            if (currentFocus > -1) {
                /*and simulate a click on the "active" item:*/
                if (x) x[currentFocus].click();
            }

            if (currentFocus === -1) {
                document.location.href = document.getElementById('btnSearch').href;
            }
        }
    })

    $('#btnResetAll').click(function () {
        resetCheckboxes($('[id^="cCommission"]'));
        resetCheckboxes($('[id^="cTheme"]'));
        resetCheckboxes($('[id^="cIntervenant"]'));
        $('#submitFilters').click();
    })

    $('#btnResetCommissions').click(function () {
        resetCheckboxes($('[id^="cCommission"]'));
    })

    $('#btnResetThemes').click(function () {
        resetCheckboxes($('[id^="cTheme"]'));
    })

    $('#btnResetIntervenants').click(function () {
        resetCheckboxes($('[id^="cIntervenant"]'));
    })

    generateURL();

    $('#sort_by').change(function () {
        generateURL();
    })

    $('#order').change(function () {
        generateURL();
    })

    $('[data-toggle="tooltip"]').tooltip();

    /*execute a function when someone clicks in the document:*/
    document.addEventListener("click", function (e) {
        closeAllLists(e.target, document.getElementById('search'));
    });
})

function addActive(x, currentFocus) {
    /*a function to classify an item as "active":*/
    if (!x) return false;
    /*start by removing the "active" class on all items:*/
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (x.length - 1);
    /*add class "autocomplete-active":*/
    x[currentFocus].classList.add("autocomplete-active");
}

function removeActive(x) {
    /*a function to remove the "active" class from all autocomplete items:*/
    for (var i = 0; i < x.length; i++) {
        x[i].classList.remove("autocomplete-active");
    }
}

function closeAllLists(elmnt, inp) {
    /*close all autocomplete lists in the document,
    except the one passed as an argument:*/
    var x = document.getElementsByClassName("autocomplete-items");
    for (var i = 0; i < x.length; i++) {
        if (elmnt === undefined || (elmnt !== x[i] && elmnt !== inp)) {
            x[i].parentNode.removeChild(x[i]);
        }
    }
}

function resetCheckboxes(array) {
    array.each(function (index, item) {
        item.checked = false;
    })
}

function generateURL() {
    let sort_select = $('#sort_by');
    let order_select = $('#order');

    let url = window.location.href.split('?');
    let params = url[1].split('&');
    let page = 'page=1';
    let term = 'term=';

    if ($('#search').val() !== '') {
        term = 'term=' + $('#search').val();
    } else {
        if (params[3] !== term) {
            term = params[3];
        }
    }


    let sort = 'sort_by=' + (sort_select.val() !== '' ? sort_select.val() : 'article_number');
    let order = 'order=' + (order_select.val() !== '' ? order_select.val() : 'asc');
    let array = [page, sort, order, term];
    let newUrl = url[0] + '?' + array.join('&');
    $('#submitSort').attr('href', newUrl);
    $('#btnSearch').attr('href', newUrl);
}