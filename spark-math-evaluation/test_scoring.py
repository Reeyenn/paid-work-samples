import unittest
from fractions import Fraction
from score import parse,score
class ScoringTests(unittest.TestCase):
    def test_exact_equivalence(self):self.assertEqual(parse('Reason\nANSWER: 2/4'),Fraction(1,2))
    def test_thinking_boundary(self):self.assertEqual(parse('internal\nANSWER: 8/9\n</think>ANSWER: -3/7'),Fraction(-3,7))
    def test_formats(self):
        for s in ['0.5','ANSWER: 1/0','ANSWER: 1/-2','ANSWER: 1/2\nMore text','ANSWER: 1/2 or 2/3','ANSWER: __import__("os")']:
            self.assertIsNone(parse(s))
    def test_no_completion_credit_for_cap(self):
        r=dict(id='x',pair='x',family='x',variant='direct',expected='1/2',budget_prefixes={'256':'ANSWER: 1/2'},output='ANSWER: 1/2',finish_reason='length',generation_tokens=256,elapsed_seconds=1)
        self.assertFalse(score(r,256)['correct']);self.assertTrue(score(r,256)['formatted_answer_matches'])
    def test_complete_exact_answer(self):
        r=dict(id='x',pair='x',family='x',variant='direct',expected='1/2',budget_prefixes={'256':'ANSWER: 2/4'},output='ANSWER: 2/4',finish_reason='stop',generation_tokens=55,elapsed_seconds=1)
        self.assertTrue(score(r,256)['correct'])
if __name__=='__main__':unittest.main()
