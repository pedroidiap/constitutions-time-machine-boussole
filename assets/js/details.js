import $ from 'jquery';

$(document).ready(function () {
    $('[id*="btnDebatMore"]').click(function () {
        let id = /\d+(?=\D*$)/.exec($(this).attr('id'))[0];
        if ($('#blockDebatMore' + id).hasClass('d-none')) {
            $('#blockDebatMore' + id).removeClass('d-none').addClass('d-inline-block');
            $('#blockDebatTruncate' + id).removeClass('d-inline-block').addClass('d-none');
            $(this).text('Voir moins');
        } else {
            $('#blockDebatMore' + id).removeClass('d-inline-block').addClass('d-none');
            $('#blockDebatTruncate' + id).removeClass('d-none').addClass('d-inline-block');
            $(this).text('Voir plus');
        }
    });

    $('#btnMoreLois').click(function () {
        let number = $(this).data('number');

        if ($('.cons-more-lois').hasClass('d-none')) {
            $('.cons-more-lois').removeClass('d-none');
            $(this).text('Réduire ' + number + ' autres');
        } else {
            $('.cons-more-lois').addClass('d-none');
            $(this).text('Afficher ' + number + ' autres');
        }
    });
});