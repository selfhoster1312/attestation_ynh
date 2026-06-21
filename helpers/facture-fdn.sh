#! /usr/bin/env bash

for var in FDN_LOGIN FDN_PASSWORD FDN_DEST; do
	if [[ "${!var}" = "" ]]; then
		echo "Missing variable for config: $var"
		exit 1
	fi
done

url="https://vador.fdn.fr/adherents"

# On se login
login_url="$(curl -Ls -o /dev/null -w %{url_effective} "${url}/index.cgi?do=yes&login=${FDN_LOGIN}&passwd=${FDN_PASSWORD}&ok=Ok")"
if echo "$login_url" | grep -P "^https:\/\/vador.fdn.fr\/adherents\/adh-in\.cgi\?sess=adh_[\d_\.]+$" 2>&1 >/dev/null; then
	sess="$(echo "$login_url" | grep -Po "sess=.+")"
	sess="${sess:5}"
	echo "Session du login: $sess"

  # On récupère la liste des factures
  fact_list="$(curl "${url}/adh-fact.cgi?sess=${sess}" 2>/dev/null)"

  # Donc on itère sur tous les liens print et on garde que le dernier
  while read -r link ; do
	  fact_id="$(echo "$link" | grep -Po 'fact=\d+-\d+-\d+-\d+')"
	  fact_id="${fact_id:5}"
  done < <(echo "$fact_list" | grep print-fact.cgi)

  echo "Facture ID: $fact_id"
  fact_url="${url}/print-fact.cgi?sess=${sess}&fact=${fact_id}"
  echo "Télécharger: $fact_url"

  if ! curl "$fact_url" > "${FDN_DEST}" 2>/dev/null; then
	  echo "ECHEC."
	  exit 1
  fi
  echo "Téléchargé dans ${FDN_DEST}"
else
	echo "Mauvais identifiant/mdp?"
	exit 1
fi
