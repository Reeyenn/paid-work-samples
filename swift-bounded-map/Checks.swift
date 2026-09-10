actor Probe {
    private var active = 0
    private var peak = 0
    private var calls = 0
    func enter() { active += 1; calls += 1; peak = max(peak, active) }
    func leave() { active -= 1 }
    func counts() -> (Int, Int, Int) { (active, peak, calls) }
}

enum ExampleError: Error { case expected }

@main
struct Checks {
    static func main() async throws {
        let probe = Probe()
        let values = Array(0..<12)
        let output = try await boundedMap(values, limit: 3) { value in
            await probe.enter()
            do {
                try await Task.sleep(for: .milliseconds(2 * (12 - value)))
                await probe.leave()
                return value * value
            } catch {
                await probe.leave()
                throw error
            }
        }
        precondition(output == values.map { $0 * $0 })
        let (active, peak, calls) = await probe.counts()
        precondition(active == 0 && peak <= 3 && calls == 12)
        print("PASS: ordered results, bounded concurrency, all tasks joined")

        let optional: [Int?] = try await boundedMap(values, limit: 2) { value in
            value.isMultiple(of: 2) ? nil : value
        }
        precondition(optional.count == 12 && optional[0] == nil && optional[1] == 1)
        print("PASS: optional output values are preserved")

        let empty: [Int] = try await boundedMap([], limit: 1) { (value: Int) in
            preconditionFailure("An empty input must not call the transform")
        }
        precondition(empty.isEmpty)
        do {
            let _: [Int] = try await boundedMap([1], limit: 0) { $0 }
            preconditionFailure("Expected invalid limit")
        } catch BoundedMapError.invalidLimit { }
        print("PASS: empty input and invalid limit")

        do {
            let _: [Int] = try await boundedMap(values, limit: 2) { value in
                if value == 0 { throw ExampleError.expected }
                try await Task.sleep(for: .seconds(5))
                return value
            }
            preconditionFailure("Expected transform failure")
        } catch ExampleError.expected { }
        print("PASS: transform error propagates")

        let cancelled = Task {
            try await boundedMap(values, limit: 2) { value in
                try await Task.sleep(for: .seconds(5))
                return value
            }
        }
        cancelled.cancel()
        do {
            _ = try await cancelled.value
            preconditionFailure("Expected cancellation")
        } catch is CancellationError { }
        print("PASS: parent cancellation propagates")
    }
}
