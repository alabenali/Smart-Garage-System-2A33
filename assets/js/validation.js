/**
 * Client-side validation for Smart Garage forms (complements PHP validation).
 */
(function () {
    'use strict';

    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (el) {
            el.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(function (el) {
            el.textContent = '';
        });
    }

    function setFieldError(input, message) {
        if (!input) return;
        input.classList.add('is-invalid');
        var group = input.closest('.sg-form-group');
        var fb = group ? group.querySelector('.invalid-feedback') : null;
        if (fb) fb.textContent = message;
    }

    window.validatePieceForm = function (form) {
        clearErrors(form);
        var ok = true;

        var reference = form.reference ? form.reference.value.trim() : '';
        var nom = form.nom ? form.nom.value.trim() : '';
        var categorie = form.categorie ? form.categorie.value : '';
        var marque = form.marque ? form.marque.value.trim() : '';
        var prix = form.prix_unitaire ? form.prix_unitaire.value.trim() : '';
        var qte = form.quantite_stock ? form.quantite_stock.value.trim() : '';
        var seuil = form.seuil_alerte ? form.seuil_alerte.value.trim() : '';

        if (!reference) {
            setFieldError(form.reference, 'La référence est obligatoire.');
            ok = false;
        } else if (!/^[A-Za-z0-9\-]{3,50}$/.test(reference)) {
            setFieldError(form.reference, '3 à 50 caractères (lettres, chiffres, tirets).');
            ok = false;
        }

        if (!nom || nom.length < 2 || nom.length > 150) {
            setFieldError(form.nom, 'Le nom doit contenir entre 2 et 150 caractères.');
            ok = false;
        }

        if (!categorie) {
            setFieldError(form.categorie, 'La catégorie est obligatoire.');
            ok = false;
        }

        if (!marque) {
            setFieldError(form.marque, 'La marque est obligatoire.');
            ok = false;
        }

        if (prix === '') {
            setFieldError(form.prix_unitaire, 'Le prix unitaire est obligatoire.');
            ok = false;
        } else if (isNaN(parseFloat(prix)) || parseFloat(prix) <= 0) {
            setFieldError(form.prix_unitaire, 'Entrez un prix valide supérieur à 0.');
            ok = false;
        }

        if (qte === '' || !/^\d+$/.test(qte) || parseInt(qte, 10) < 0) {
            setFieldError(form.quantite_stock, 'Quantité entière positive ou 0.');
            ok = false;
        }

        if (seuil === '' || !/^\d+$/.test(seuil) || parseInt(seuil, 10) < 0) {
            setFieldError(form.seuil_alerte, 'Seuil entier positif ou 0.');
            ok = false;
        }

        return ok;
    };

    window.validateOrderForm = function (form) {
        clearErrors(form);
        var ok = true;

        var nom = form.nom_client ? form.nom_client.value.trim() : '';
        var prenom = form.prenom_client ? form.prenom_client.value.trim() : '';
        var tel = form.telephone ? form.telephone.value.trim() : '';
        var idPiece = form.id_piece ? form.id_piece.value : '';
        var qte = form.quantite ? form.quantite.value.trim() : '';

        if (!nom || nom.length < 2 || nom.length > 150) {
            setFieldError(form.nom_client, 'Le nom est obligatoire (2–150 caractères).');
            ok = false;
        }

        if (!prenom || prenom.length < 2 || prenom.length > 150) {
            setFieldError(form.prenom_client, 'Le prénom est obligatoire (2–150 caractères).');
            ok = false;
        }

        if (!tel || !/^[\d\s\+\-]{8,20}$/.test(tel)) {
            setFieldError(form.telephone, 'Téléphone valide (8–20 caractères).');
            ok = false;
        }

        if (!idPiece) {
            setFieldError(form.id_piece, 'Sélectionnez une pièce.');
            ok = false;
        }

        if (!qte || !/^\d+$/.test(qte) || parseInt(qte, 10) < 1 || parseInt(qte, 10) > 999) {
            setFieldError(form.quantite, 'Quantité entre 1 et 999.');
            ok = false;
        }

        if (idPiece && qte && /^\d+$/.test(qte) && form.id_piece.selectedOptions.length) {
            var opt = form.id_piece.selectedOptions[0];
            var stock = parseInt(opt.getAttribute('data-stock'), 10);
            if (!isNaN(stock) && parseInt(qte, 10) > stock) {
                setFieldError(form.quantite, 'Stock insuffisant (' + stock + ' disponible(s)).');
                ok = false;
            }
        }

        return ok;
    };
})();
