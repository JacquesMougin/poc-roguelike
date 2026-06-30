import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static targets = ['display']
  static values = { url: String, seconds: { type: Number, default: 5 } }

  connect() {
    this.remaining = this.secondsValue
    this.render()
    this.timer = setInterval(() => {
      this.remaining--
      if (this.remaining <= 0) {
        clearInterval(this.timer)
        window.location.href = this.urlValue
      } else {
        this.render()
      }
    }, 1000)
  }

  disconnect() {
    clearInterval(this.timer)
  }

  render() {
    if (this.hasDisplayTarget) {
      this.displayTarget.textContent = this.remaining
    }
  }
}
