// This function gets your whole document as its `body`
// and formats it as a simple letter.
#let letter(
  // The letter's sender, which is display at the top of the page.
  sender: none,

  // The letter's recipient, which is displayed close to the top.
  recipient: none,

  // The date, displayed to the right.
  date: none,

  // The subject line.
  subject: none,

  // The name with which the letter closes.
  name: none,

  // The signature
  sig: none,

  // The letter's content.
  body
) = {
  // Configure page and text properties.
  set page(margin: (top: 2cm))
  set text(font: "PT Sans")

  // Display sender at top of page. If there's no sender
  // add some hidden text to keep the same spacing.
  text(9pt, if sender == none {
    hide("a")
  } else {
    sender
  })

  v(1.8cm)

  // Display recipient.
  par(leading: 0.4em, text(weight: "extrabold", size: 14pt, recipient))

  v(0.5cm)

  // Display date. If there's no date add some hidden
  // text to keep the same spacing.
  align(right, if date != none {
    par(leading: 0.4em, date)
  } else {
    hide("a")
  })

  v(2cm)

  // Add the subject line, if any.
  if subject != none {
    align(center, text(weight: "extrabold", size: 18pt, subject))
  }

  // Add body and name.
  body
  v(1.25cm)
  align(right, name)

  if sig != none {
    align(right, image(sig))
  }
}
