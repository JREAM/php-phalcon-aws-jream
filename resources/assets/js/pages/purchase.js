// -----------------------------------------------------------------------------
// Document Ready
// -----------------------------------------------------------------------------
$(() => {

  // -----------------------------------------------------------------------------

  $("#formPurchaseStripe").submit(function (evt) {
    evt.preventDefault();

    const url = $(this).attr("action");

    axios.get(url).then(resp => {

    })
      .catch(err => {
        $(this).notify(err.msg, "error");
      });

  });

  // -----------------------------------------------------------------------------

});
