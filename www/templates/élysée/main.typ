#import "../lib.typ": letter

#show: letter.with(
  recipient: [
    *Nicolas Sarkozy \
    55 rue du Faubourg Saint-Honoré \
    75008 Paris* \
  ],
  date: [Paris,\ le {{docdate}}],
  subject: [Attestation d'hébergement],
  name: [Nicolas Sarkozy \ Président de la république],
  sig: "templates/élysée/élysée.png",
)

Je soussigné Nicolas Sarkozy, président de la République Française, atteste
par la présente héberger {{name}} (né-e le {{birthdate}} à {{birthplace}})
au palais de l'Élyée, 55 rue du Faubourg Saint-Honoré à Paris (75008), depuis le  {{arrivaldate}}.

\
\
Fait pour valoir ce que de droit.
