package goa

import (
	"bytes"
	"io/ioutil"
	"net/http"
	"net/url"

	"github.com/goadesign/goa"
	. "github.com/onsi/ginkgo"
	. "github.com/onsi/gomega"
)

var _ = Describe("Mux", func() {
	var mux goa.ServeMux

	var req *http.Request
	var rw *TestResponseWriter

	BeforeEach(func() {
		mux = goa.NewMux()
	})

	JustBeforeEach(func() {
		rw = &TestResponseWriter{ParentHeader: http.Header{}}
		mux.ServeHTTP(rw, req)
	})

	Context("with no handler", func() {
		BeforeEach(func() {
			var err error
			req, err = http.NewRequest("GET", "/", nil)
			Ω(err).ShouldNot(HaveOccurred())
		})
		It("returns 404 to all requests", func() {
			Ω(rw.Status).Should(Equal(404))
		})
	})

	Context("with registered handlers", func() {
		const reqMeth = "POST"
		const reqPath = "/foo"
		const reqBody = "some body"

		var readMeth, readPath, readBody string

		BeforeEach(func() {
			var body bytes.Buffer
			body.WriteString(reqBody)
			var err error
			req, err = http.NewRequest(reqMeth, reqPath, &body)
			Ω(err).ShouldNot(HaveOccurred())
			mux.Handle(reqMeth, reqPath, func(rw http.ResponseWriter, req *http.Request, vals url.Values) {
				b, err := ioutil.ReadAll(req.Body)
				Ω(err).ShouldNot(HaveOccurred())
				readPath = req.URL.Path
				readMeth = req.Method
				readBody = string(b)
			})
		})

		It("handles requests", func() {
			Ω(readMeth).Should(Equal(reqMeth))
			Ω(readPath).Should(Equal(reqPath))
			Ω(readBody).Should(Equal(reqBody))
		})
	})

})